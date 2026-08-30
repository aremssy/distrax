# Distrax — AI Agent Build Blueprint v3 (Merged / Final)
Supersedes v1 and v2 — use this doc going forward, the other two are now historical. Stack: Laravel 13 + MySQL, existing multi-tenant real estate marketplace codebase (Sanctum, ~70 models, ~90 migrations, Blade+Vite).

## Contents
- Part 0 — Integration principles (paste once, first)
- Part 1 — Database schema (EXTEND + NEW)
- Part 2 — Workflow: audit → decide → generate schema → build features
- Part 2a — Schema audit prompt (paste-ready)
- Part 2b — Schema generation prompt (paste-ready)
- Part 3 — Feature prompts, one per module, fully self-contained
- Part 4 — Global UI/UX rules
- Part 5 — Open decisions to resolve before/during the build

---

## Part 0 — Integration Principles

Paste this once at the start of your agent session, before anything else:

```
This is an EXISTING Laravel 13 app (multi-tenant real estate marketplace,
Sanctum auth, ~70 models, ~90 migrations, Blade+Vite frontend). Every
prompt I give you from here on is a modification to this app, not a
greenfield build. Follow these rules on every task without me repeating
them:

1. Before adding any table or column, check whether an existing model/
   migration already covers it. I'll name the likely existing analog in
   each prompt — verify it against the real columns, don't just trust my
   note.
2. Prefer extending an existing model/table with new columns over creating
   a parallel table, UNLESS the existing table's grain is genuinely
   different (e.g. a general-purpose Favorite is not the same grain as an
   investment watchlist with target-price tracking — decide based on what
   the data actually needs to hold, not just naming similarity).
3. Foreign keys to the core listing table are `property_listing_id`
   (matching the existing `PropertyListing` model) — never `property_id`.
4. Reuse existing services rather than writing new ones that duplicate
   them: GatewayFactory for any payment, NotificationCenter/
   NotificationDispatcher for any alert, AuditLogger for any audit trail,
   SavedSearchMatcherService for any saved-criteria matching,
   DistanceService/PointInPolygon for any geo/zone logic,
   ExportManager/CsvStreamExporter for any bulk export, the existing
   Csv*Importer classes for any bulk import.
5. New permission strings follow the existing convention
   (`resource.action`, e.g. `verification-cases.view`) and register through
   the existing Permission/Role system — no second permission mechanism.
6. New admin CRUD follows the existing Admin/ controller + Blade component
   pattern; new API endpoints go under versioned Api/V1/, documented via
   Scribe like the rest.
7. If an assumed existing analog doesn't actually fit once you look at its
   real columns, stop and tell me the mismatch — don't silently build a
   workaround.
```

---

## Part 1 — Database Schema

### 1a. EXTEND — add columns to existing tables

| Existing table/model | New columns | Powers |
|---|---|---|
| `users` (`User`) | `buying_for` ENUM(my_home,investment,fix_flip,development,land_banking,commercial) NULL, `kyc_status` ENUM(unverified,pending,verified,rejected) DEFAULT unverified, `is_institutional` BOOLEAN DEFAULT false | 3.14 personalization, KYC gating |
| `property_listings` (`PropertyListing`) | `distress_reason_category` ENUM(divorce,relocation,debt,estate_probate,bank_repossession,urgent_cash_need,other) NULL, `distress_reason_visibility` ENUM(public,disclosure_only,private) DEFAULT disclosure_only, `expected_closing_period` ENUM(flexible,30_days,60_days,90_days,immediate), `negotiation_flexibility` ENUM(firm,negotiable,highly_negotiable,make_an_offer), `expected_market_value` DECIMAL(15,2) NULL, `deal_score_cached` DECIMAL(5,2) NULL, `verification_case_id` FK NULL | 3.4, 3.6, 3.7 |
| `payments` (`Payment`) | `purpose` ENUM(sale,verification_fee,premium_listing,transaction_commission,inspection_fee,valuation_report,professional_subscription,institutional_saas,escrow_fee) DEFAULT sale | Table 7 revenue routed through existing gateways |
| `saved_searches` (`SavedSearch`) | `is_mandate` BOOLEAN DEFAULT false, `min_discount_pct` NULL, `min_deal_score` NULL, `frequency` ENUM(instant,daily_digest,weekly_digest) DEFAULT instant | 3.11 Deal Radar |
| `favorites` (`Favorite`) | `is_watchlist` BOOLEAN DEFAULT false, `target_price` NULL, `notes` TEXT NULL | Watchlist — only if Part 5 decision #3 confirms this fits |
| `visit_schedules` (`VisitSchedule`) | `type` ENUM(physical,virtual) DEFAULT physical, `inspector_id` FK users NULL, `checklist` JSON NULL, `gps_lat`, `gps_lng`, `report_url` NULL, `issues` TEXT NULL, `buyer_acknowledged_at` NULL | Inspections — only if Part 5 decision #2 confirms this fits |
| `user_notifications` (`UserNotification`) | extend `type` enum/lookup with: `price_drop`, `verification_completed`, `new_matched_deal`, `disclosure_added`, `offer_status_change`, `verification_expiring` | 3.11, 3.2 |
| `reviews` (`Review`) | `deal_id` FK NULL | 3.16 seller reputation |

### 1b. NEW — genuinely new tables

FKs use `property_listing_id` per Part 0. `seller_profiles`, `inspections`, `watchlists` are conditional on Part 5 decisions #1–3 — build only if the audit confirms no existing table fits.

```
user_kyc
  id, user_id (FK), id_type, id_number, id_document_url, selfie_url,
  status ENUM(pending,verified,rejected), verified_by (FK users), verified_at
  -- CHECK FIRST: Profile/ProfileController already has a "verification"
  -- feature — confirm it doesn't already cover this before building.

seller_profiles                        -- CONDITIONAL, see Part 5 #1
  id, user_id (FK), seller_type ENUM(individual,company,estate,
      executor_administrator,bank_institution,agent,developer),
  company_name NULL, poa_or_probate_doc_url NULL,
  response_time_avg_minutes, offer_response_rate, completed_transactions_count,
  disclosure_compliance_score, buyer_feedback_avg

institutional_accounts
  id, org_name, org_type ENUM(bank,asset_management,receiver,insurer,
      corporate,estate_administrator,developer), primary_contact_user_id (FK),
  created_at

bulk_upload_batches
  id, institutional_account_id (FK), file_url, total_rows, processed_rows,
  status ENUM(queued,processing,completed,failed), created_by (FK users)

property_documents
  id, property_listing_id (FK), doc_type ENUM(title,survey,building_planning,
      inspection_report,verification_report,legal_report,offer_document,
      closing_document), file_url, uploaded_by (FK users),
  visibility_level ENUM(public,verified_buyer,nda_stage,internal_only)
      DEFAULT internal_only, verified BOOLEAN DEFAULT false

price_history                          -- CHECK Part 5 #4 first
  id, property_listing_id (FK), old_price, new_price, changed_at,
  changed_by (FK users)

property_timeline_events               -- CHECK Part 5 #4 first
  id, property_listing_id (FK), event_type ENUM(listed,price_change,
      verification_completed,inspection_booked,offer_made,status_change,
      disclosed_change), description, privacy_level ENUM(public,
      aggregate_only,internal), occurred_at

verification_cases
  id, property_listing_id (FK), status ENUM(distrax_verified,
      disclosure_required,in_progress,under_legal_review,not_verified)
      DEFAULT in_progress, assigned_officer_id (FK users), opened_at,
  closed_at, expiry_review_date

verification_tasks
  id, case_id (FK), layer ENUM(seller_kyc,document_review,title,survey,
      physical,ownership_authority,encumbrance,litigation,planning,
      final_decision),
  owner_role ENUM(distrax,legal,property_lawyer,licensed_surveyor,
      distrax_inspector,surveyor_planning_professional),
  status ENUM(not_started,in_progress,passed,failed,flagged), notes,
  assigned_to (FK users), completed_at

verification_evidence
  id, task_id (FK), file_url, evidence_type, description, uploaded_by (FK users),
  created_at

verification_scores
  id, property_listing_id (FK), case_id (FK), reference_id (unique,
      DTX-VER-{6 digit}), score DECIMAL(5,2), seller_verification_status,
  title_status, ownership_status, survey_status, physical_inspection_status,
  legal_review_status, planning_status, disclosure_count INT DEFAULT 0,
  verification_date, expiry_review_date NULL, qr_code_url

disclosures
  id, property_listing_id (FK), category, description,
  severity ENUM(low,medium,high), visible_publicly BOOLEAN DEFAULT true,
  created_by (FK users)

valuations
  id, property_listing_id (FK), estimated_market_value, price_per_sqm,
  discount_or_premium_pct, methodology, confidence_level ENUM(low,medium,
      high), estimated_acquisition_cost, estimated_renovation_cost NULL,
  estimated_exit_value NULL, computed_at

comparable_properties
  id, property_listing_id (FK), comparable_property_listing_id
      (FK property_listings, NULL if external), external_source NULL,
  price, distance_km, similarity_score

deal_scores
  id, property_listing_id (FK), total_score DECIMAL(5,2),
  discount_component, verification_component, location_component,
  condition_component, urgency_component, negotiation_component,
  comparable_position_component, income_potential_component,
  liquidity_component, risk_penalty_component,
  label ENUM(weak,fair,good,strong,exceptional_opportunity), computed_at

risk_assessments
  id, property_listing_id (FK), risk_area ENUM(title,ownership,legal,
      occupancy,physical_condition,planning,liquidity,
      transaction_complexity), level ENUM(low,medium,high),
  why_explanation, evidence_ref_id NULL

investment_calculators
  id, property_listing_id (FK), view_type ENUM(buy_hold,fix_flip,
      development,owner_occupier,land_banking,commercial), inputs JSON,
  outputs JSON, computed_at

inspections                            -- CONDITIONAL, see Part 5 #2
  id, property_listing_id (FK), buyer_id (FK users), type ENUM(physical,
      virtual), inspector_id (FK users) NULL, scheduled_at,
  status ENUM(requested,confirmed,completed,cancelled,no_show), gps_lat,
  gps_lng, checklist JSON, report_url NULL, issues TEXT NULL,
  buyer_acknowledged_at NULL

offers
  id, property_listing_id (FK), buyer_id (FK users), amount,
  status ENUM(pending,countered,accepted,rejected,expired,withdrawn),
  expiry_at, inspection_condition BOOLEAN DEFAULT false,
  legal_review_condition BOOLEAN DEFAULT false, created_at

negotiations
  id, offer_id (FK), actor_id (FK users), message NULL, counter_amount NULL,
  created_at

deals
  id, property_listing_id (FK), offer_id (FK), buyer_id (FK), seller_id (FK),
  stage ENUM(offer_accepted,inspection,legal_review,closing,completed,
      fell_through), deal_manager_id (FK users), commission_amount NULL

legal_matters
  id, deal_id (FK), reviewer_id (FK users), status ENUM(pending,in_review,
      cleared,issue_found), notes

portfolios
  id, user_id (FK), property_listing_id (FK), ownership_type ENUM(owned,
      considering), acquisition_price NULL, acquisition_date NULL,
  income_records JSON NULL, cost_records JSON NULL, current_valuation NULL,
  performance_metrics JSON NULL

watchlists                             -- CONDITIONAL, see Part 5 #3
  id, user_id (FK), property_listing_id (FK), added_at

ask_distrax_queries
  id, user_id (FK), property_listing_id (FK), question, answer,
  cited_evidence JSON, answer_type ENUM(verified_fact,estimate,
      recommendation), created_at
```

**Indexing notes:** composite index on `property_listings(status, deal_score_cached)`; unique index on `verification_scores.reference_id`; full-text index on `property_listings(title, description)`. Geo/radius filtering reuses the existing `Zone`/`PointInPolygon`/`DistanceService` — do not add new spatial columns.

---

## Part 2 — Workflow

Run these in order. Don't skip the audit step even though you now have this merged doc — Part 1 above is still my best guess from a structure overview, not verified column-level truth.

1. **Audit** — run Part 2a against the real repo. It confirms or corrects every EXTEND/NEW call in Part 1.
2. **Decide** — resolve the four conditional items in Part 5 (#1–4) using what the audit turned up.
3. **Generate schema** — run Part 2b: add-column migrations for everything confirmed EXTEND, full migrations+models+factories+seeders for everything confirmed NEW.
4. **Build features** — run the Part 3 prompts in MVP order (P0 modules first: Verify → Market → Intelligence's Deal Score/Risk → Inspect → Offers → Admin; then P1; then P2; P3 stays a stub per its own prompt).

---

## Part 2a — Schema Audit Prompt (paste-ready)

```
You're adding schema for new Distrax features to an EXISTING Laravel 13
multi-tenant real estate marketplace app. I have a proposed EXTEND/NEW
schema (below) built from a structure overview, not verified column-level
detail — your job is to verify it against the real migrations and models,
not rediscover the app from scratch.

For every table in my "EXTEND" list, read the actual migration + model and
report: exact existing columns/types, exact existing relationships, and
whether my proposed new columns are genuinely missing or already covered
under a different name. Tables to check: users, property_listings,
payments, saved_searches, favorites, visit_schedules, user_notifications,
reviews.

For every table in my "NEW" list, do a quick check that nothing already
covers it under a name I don't know about, then confirm it's genuinely new.
Tables: user_kyc, seller_profiles, institutional_accounts,
bulk_upload_batches, property_documents, price_history,
property_timeline_events, verification_cases, verification_tasks,
verification_evidence, verification_scores, disclosures, valuations,
comparable_properties, deal_scores, risk_assessments,
investment_calculators, inspections, offers, negotiations, deals,
legal_matters, portfolios, watchlists, ask_distrax_queries.

Specifically resolve these four open questions — read the actual code
before answering, don't guess:
1. Is "seller" identity/KYC/company data currently modeled as columns on
   User, or is there no existing analog? Look at OwnerListingController and
   AgencyController's current patterns.
2. Read VisitSchedule's actual columns and VisitController's actual flow.
   Is it a thin calendar-slot booking, or does it already have anything
   evidence/report/GPS-shaped? Tell me whether extending it or building a
   separate `inspections` table is the better fit.
3. Read Favorite's actual columns/usage. Is `is_watchlist` + a couple of
   extra columns enough, or does an investment watchlist need to be a
   separate table?
4. Check whether AuditLog already captures enough of PropertyListing's
   change history (price changes, status transitions) that price_history
   and property_timeline_events can be derived from it instead of built as
   new tables.

Also: search the FULL codebase (not just migrations) for raw table-name
references (DB::table(), raw SQL) touching any EXTEND-list table, since
those won't show as Laravel-detected foreign keys and would break silently
if I add columns with conflicting names.

Output a written report only — no migrations yet. Wait for my answers on
the four open questions before generating anything.

--- SCHEMA START ---
[paste Part 1 of this doc here]
--- SCHEMA END ---
```

---

## Part 2b — Schema Generation Prompt (paste-ready)

Run only after Part 2a's report comes back and you've resolved Part 5's open items.

```
Using the audit report you just produced and my answers to the open
questions, now generate the actual schema changes.

For every table confirmed EXTEND: generate a single additive migration per
table (add columns only — never a destructive change to an existing
column without flagging it to me first). Update the corresponding
Eloquent model's $fillable/casts/relationships to include the new columns.

For every table confirmed NEW: generate the migration (proper foreign keys,
onDelete cascade for child records like property_documents/
verification_evidence when their parent is deleted, restrict/no-action for
anything transaction-adjacent like deals/transactions/legal_matters), the
Eloquent model (relationships, casts, $fillable, soft deletes where
sensible), a factory with realistic Nigerian-context fake data (Naira
amounts, Lagos/Abuja-plausible addresses), and register it in a seeder.

Order all migrations by dependency (users/property_listings-dependent
tables first, then anything referencing verification_cases, then anything
referencing deals).

Build one consolidated seeder addition that creates: ~20 property_listings
spread across verification statuses (some distrax_verified, some
in_progress, some not_verified) with their related verification_cases/
tasks/scores, a handful of deal_scores and risk_assessments, and a few
completed deals — enough to exercise every new status/enum value at least
once without touching or reseeding existing data.

Run the migrations against a fresh test DB copy, confirm no FK errors
against existing tables, run the seeder addition, and confirm it completes
without disturbing existing seeded data. Show me any errors and how you
fixed them.

Do NOT touch controllers, routes, or business logic in this step — schema,
models, factories, seeders only.
```

---

## Part 3 — Feature Prompts

### MODULE: Distrax Verify

#### 3.1 Verification Case Management (P0)
```
Build a verification case-management system: verification_cases,
verification_tasks, verification_evidence, verification_scores,
disclosures (schema per Part 1b — entirely new, no existing analog).

Business rules:
- When property_listings enters the verification-eligible state (map onto
  the existing approve/reject/archive status flow rather than adding a
  conflicting status — confirm the mapping with me if ambiguous),
  auto-create a verification_case (status=in_progress) and one
  verification_task per layer: seller_kyc, document_review, title, survey,
  physical, ownership_authority, encumbrance, litigation, planning,
  final_decision.
- Route task assignment/visibility by owner_role: seller_kyc +
  document_review + final_decision → verification_officer; title,
  ownership_authority, encumbrance, litigation → legal_reviewer; survey,
  planning → surveyor; physical → inspector.
- Assigned staff attach verification_evidence (file + description) to a
  task and mark it passed/failed/flagged with notes.
- final_decision can only pass once every other task is passed or
  explicitly waived with a reason (store the waiver reason in
  verification_tasks.notes).
- On final_decision=passed with zero unresolved issues → status =
  distrax_verified, generate verification_scores with a unique
  reference_id (DTX-VER-{6 digit}) and a QR code encoding
  /verify/{reference_id}, copying each task's outcome into the matching
  *_status column.
- On final_decision=passed but disclosures exist → status =
  disclosure_required (require ≥1 linked disclosures record before this
  transition is allowed).
- On final_decision=failed, legal-only issue → under_legal_review;
  otherwise → not_verified.
- Verification is time-bound: store verification_date +
  expiry_review_date; a scheduled job flags cases past that date for
  re-review — never silently downgrade the badge, create a task for a
  verification_officer to re-confirm instead.
- Build a verification_officer dashboard: open cases, tasks assigned to
  me, turnaround time per case, queue filterable by layer/status.

Integration:
- FK verification_cases.property_listing_id → property_listings.id.
- Every status change, evidence upload, and disclosure add writes through
  the existing AuditLogger service — these must show up in the same
  AuditLogController the admin already uses, not a new log.
- New admin screens (officer queue, task detail, evidence upload) go
  under app/Http/Controllers/Admin/, following ListingController's
  approve/reject/archive pattern and the existing Blade component library.
- New permissions (verification-cases.view/assign/finalize,
  verification-tasks.update) register through the existing Permission/
  Role system and gate routes with the existing `permission:*` middleware.
- Check whether the existing Role model can just take new Role rows
  (verification_officer, legal_reviewer, surveyor, inspector, deal_manager,
  finance) without structural changes before assuming you need to modify
  Role itself.
- On finalize, write verification_case_id + deal_score_cached back onto
  property_listings and set the badge status per 3.2.
```

#### 3.2 Verification Status Badge (P0)
```
Implement the 5-state badge, sourced from verification_cases.status:

| Status | Meaning | Marketplace treatment |
|---|---|---|
| 🟢 Distrax Verified | Core scope passed, no material unresolved issue | Full visibility |
| 🟡 Verified — Disclosure Required | Verified but material matters must be disclosed | Visible, disclosure prominent, never collapsed by default |
| 🔵 Verification in Progress | Checks incomplete | Reduced visibility (no price-per-sqm comps, no "make offer" until further along — confirm exact gate with me before building it, the doc doesn't specify the cutoff) |
| 🟠 Under Legal Review | Issue requires professional assessment | No verified badge or verified copy anywhere |
| 🔴 Not Verified | Verification failed / unresolved material issue | Not publishable as verified; if shown, clearly labeled unverified |

Build as a single Blade component (resources/views/components/) — check
how ListingController currently shows approved/pending/archived state in
the property card partial and follow that convention rather than building
a second badge system. Every place a property card renders (search
results, SavedSearch/Deal Radar digest emails, favorites/watchlist,
comparables, portfolio) resolves the badge through this one component.

Anywhere "Distrax Verified" appears, footer/tooltip copy must state
verification is a defined process as of a stated date, not a guarantee
the transaction can't fail — never word this as a guarantee.
```

#### 3.3 Verification Passport (P0)
```
Build the passport page/component from verification_scores +
verification_cases + property_documents. Fields: reference ID,
verification date, verification score, seller verification status, title
status, ownership status, survey status, physical inspection status,
legal review status, planning status, disclosure count, expiry/review date
(if applicable), QR code.

The QR code resolves to a PUBLIC route /verify/{reference_id} (outside
auth middleware, following the same public-route pattern as
/properties/{slug}) showing only the summary fields above — no seller PII,
no document access, works for unauthenticated scans.

"Full evidence/report access" (verification_evidence files,
property_documents) is gated by role/permission. Before building
user_kyc as a new table, check whether ProfileController's existing
"verification" feature already gives you a usable kyc_status equivalent —
reuse it if so. Define "qualified buyer" as an explicit condition (e.g.
kyc verified AND active NDA/offer on this property) — do not expose raw
document URLs to anonymous or unverified users under any circumstance.
```

### MODULE: Distrax Market

#### 3.4 Seller Onboarding & Property Intake (P0)
```
Extend the existing owner listing creation flow (OwnerListingController,
Api/V1/Listing/ListingController) — don't build a new wizard. First
confirm what the current create/edit flow already collects (type, price,
size, location via Zone, media via PropertyImage/PropertyVideo, custom
fields via CustomField/CustomFieldValue are likely already there) and add
only what's missing:

- seller_type + company_name/poa_or_probate_doc_url (location depends on
  Part 5 decision #1 — seller_profiles or User columns)
- distress_reason_category + distress_reason_visibility (new columns,
  Part 1a) — the visibility control must be shown and explained at entry,
  not buried in settings
- expected_closing_period, negotiation_flexibility (new columns, Part 1a)
- expected_market_value, seller-declared (new column, Part 1a) — label
  clearly as the seller's own estimate, distinct from Distrax's valuation
- Consider using the existing CustomField/CustomFieldValue system for
  property condition and occupancy/tenancy status instead of hardcoded
  enums, if it supports the needed field types — check before adding new
  columns for these two.
- Title/supporting documents upload → property_documents (new table)
- Images/video/inspection-access toggle → likely already covered by
  PropertyImage/PropertyVideo; just add the inspection-access toggle.

On submit, when status reaches the verification-eligible state (per
ListingController's existing approve/reject/archive gating), trigger
verification_case creation (3.1). Don't let it appear in public search
before the badge rules in 3.2 allow it — hook into however
ListingController currently hides pending/archived listings from
`/properties`.
```

#### 3.5 Buyer Search, Filters, Feed, Ranking (P0 core filters; P1 deeper filters)
```
Extend the existing `/properties` search (PropertyController web +
Api/V1/Listing/ListingController) — don't build a parallel search
endpoint. Confirm existing filters (zone, price range, property type,
CustomField-driven filters) before adding.

P0 filters to add: verification status, deal type tag (urgent sale / below
market value / bank-institutional asset / estate sale / owner distress /
price reduced / renovation opportunity / development opportunity).

P1 filters (sequence after 3.7/3.8 since they need deal_scores/valuations
to exist): discount bands (10/20/30/40/50%+), urgency, negotiation
flexibility, property condition, title type/status, investment strategy,
yield threshold, commute time (reuse DistanceService — no new distance
calculator), recent-price-reduction flag (needs price_history), risk
tolerance, Deal Score threshold slider. Validate filter combinations and
show a "no matches, try relaxing X" state rather than a silent empty
result.

Ranking: layer these onto whatever ordering already exists rather than
replacing it — verification status, Deal Score, buying_for relevance,
location match, price/value attractiveness, freshness, seller
responsiveness (from seller_profiles), data completeness, inspection
availability, saved-search match strength. Sponsored listings (check
ListingPackage for how "promoted" already works) render labeled and can
never outrank a verified listing purely on sponsorship — sponsorship
can't override the 3.2 verification gate.
```

#### 3.6 Property View Page (P0)
```
Extend the existing property detail template (served by
PropertyController) — don't build a new page. First report back what
sections already exist and in what order, then insert the new ones into
this sequence:

hero media → Deal Score + verification badge (NEW) → asking price +
estimated market value + discount (extend existing price display) →
urgency/negotiation indicators (NEW) → sale reason, privacy-safe (NEW) →
Verification Passport (NEW) → Deal Analysis (NEW) → Risk Snapshot (NEW) →
Disclosures Center (NEW) → property details/specs (likely exists via
CustomFieldValue) → Location Intelligence (extend existing Zone-based
map) → Investment Potential (NEW) → Price History (NEW) → Property
Timeline (NEW) → Document Vault (NEW, permissioned per 3.3) → Comparable
Properties (NEW) → inspection availability (extend existing
VisitSchedule/inspections booking UI) → Offers (NEW) → Ask Distrax
(P2, feature-flagged) → Decision Summary (NEW, label clearly as
Distrax-generated, not advice) → sticky "Make Offer"/"Book Inspection" CTA
(extend existing contact/book-visit CTA area).

This section order is a deliberate trust-then-detail-then-action
sequence — don't reorder for "better conversion" without flagging it back
to me first.
```

### MODULE: Distrax Intelligence

#### 3.7 Deal Score Engine (P0)
```
Build the Deal Score as an explainable, versioned scoring service — new
tables (deal_scores), no existing analog.

Inputs: discount to estimated market value, verification confidence,
location attractiveness, property condition, seller urgency, negotiation
flexibility, comparable-market position, rental/income potential (where
relevant), liquidity/exit potential, known risk and disclosure impact (as
a penalty). Output: total_score (0–100) + every component stored
individually for a UI breakdown, never just a headline number. Display:
"Distrax Deal Score {score}/100 — {label}" (weak/fair/good/strong/
exceptional, thresholds configurable). Write a unit test asserting a
low-price/high-risk property scores below a moderate-price/low-risk one —
discount alone must never dominate the score.

Integration: on compute, write a new deal_scores row and back-fill
property_listings.deal_score_cached (Part 1a) so existing search/sort can
order by it without a join. Recompute on: verification status change
(hook 3.1's case finalize), price change (check for an existing "price
changed" event/observer on PropertyListing before adding a new one), new
disclosure, new comparable.
```

#### 3.8 Market Value, Deal Analysis, Risk Snapshot (P0)
```
Build valuations, comparable_properties, risk_assessments — new tables,
no existing analog, except comparable matching should reuse the existing
Zone/DistanceService for "same submarket" logic rather than a new geo
query.

Deal Analysis block shows: estimated market value, asking price, price
per sqm, discount/premium %, comparables, price history, recent price
reductions, estimated acquisition/renovation cost, estimated exit value.
Every model-generated number renders with an "Estimate" label + its
confidence_level — never presented as fact.

Risk Snapshot shows all 8 risk areas (title, ownership, legal, occupancy,
physical/condition, planning, liquidity, transaction complexity) even when
Low — don't hide low-risk rows. Each row expands to its why_explanation +
evidence_ref_id link; a risk level with no "why" attached fails validation
server-side, not just looks sparse in the UI.
```

#### 3.9 Investment Intelligence Calculators (P1)
```
Build investment_calculators, one view per buying_for: Buy & Hold
(estimated rent, gross yield, expenses, cash flow), Fix & Flip (purchase
price, renovation estimate, projected resale, gross margin), Development
(land size, planning context, feasibility prompts), Owner Occupier
(affordability, condition, location, livability), Land Banking
(acquisition price, title status, location drivers, holding horizon),
Commercial (occupancy, rent, yield, lease context).

Default the property page's Investment Potential section to the view
matching users.buying_for; let the buyer switch manually. Store input
overrides (e.g. their own rent assumption) per-session, not permanently
against the listing.
```

#### 3.10 Comparables & Timeline (P1)
```
Build comparable_properties (submarket + size/type/condition similarity,
ranked by similarity_score, reusing Zone/DistanceService).

Before building price_history and property_timeline_events as new tables,
check whether the existing AuditLog already captures enough of
PropertyListing's change history (price changes, status transitions) to
derive most of the timeline from AuditLog entries filtered by
entity_type=PropertyListing — report back before building parallel
tables.

Timeline shows: listing date, price changes, verification completion,
inspection events, offer events as privacy-safe aggregates only (e.g. "3
offers received", never buyer identity or competing amounts), major
disclosed changes, status transitions. Unknown history renders as an
explicit "Not available" state — never fabricated or inferred.
```

### MODULE: Distrax Radar

#### 3.11 Deal Radar (P1)
```
Extend the existing SavedSearch + SavedSearchMatcherService — do not build
a new deal_radar_rules table. Add is_mandate, min_discount_pct,
min_deal_score, frequency (Part 1a) to saved_searches.

Extend SavedSearchMatcherService to also evaluate min_discount_pct and
min_deal_score once deal_scores exists. New alert types (instant on new
matching inventory, daily/weekly digest, price-drop, verification-
completed, new-comparable, seller-urgency-change) route through the
existing NotificationCenter/NotificationDispatcher — add new
user_notifications.type values (Part 1a) rather than a separate alerting
pipeline. Check whether NotificationDispatcher already supports queued vs
scheduled delivery before building new scheduling logic.
```

### MODULE: Distrax Inspect

#### 3.12 Inspection Booking & Reports (P0)
```
Default assumption: extend VisitSchedule (Part 1a columns: type,
inspector_id, checklist, gps_lat/lng, report_url, issues,
buyer_acknowledged_at) rather than a new inspections table.

Before proceeding, read VisitSchedule's actual columns and
VisitController's actual flow and confirm this fits — a basic
"visit request" calendar slot is a different shape from "assign
inspector + GPS-verified evidence + structured checklist + buyer
acknowledgement." If VisitSchedule is genuinely just a calendar booking
with no evidence/report concept, use a separate `inspections` table
(Part 1b) linked to visit_schedules instead of overloading it, and tell me
which way you went.

Buyer can book physical or virtual inspection, choose or get auto-assigned
an inspector, pick a slot. On completion, the inspector submits
GPS/timestamped evidence, a structured checklist (JSON, queryable — not
free text), a photo/video report, issues/recommendations. Buyer must
acknowledge the report before an offer's inspection_condition can be
satisfied. GPS/timestamp should be captured device-side and validated
server-side (reject uploads with missing/implausible geodata for a
physical inspection).

Inspector queue: check whether TechnicianBookingController's
assignment/status pattern is close enough to reuse for inspector
assignment, since Technician already has a booking + calendar concept.
```

### MODULE: Distrax Offers

#### 3.13 Offers & Transaction Workflow (P0)
```
Build offers, negotiations, deals, legal_matters — genuinely new, no
existing analog (Booking/TechnicianBooking are for technician services,
not property transactions).

Flow: Make Offer → seller Accept/Reject/Counter → negotiations records
each counter → on Accept, create deals at stage=offer_accepted → progress
through inspection → legal_review → closing → completed (or
fell_through with a reason at any stage). Offers carry expiry_at; a
scheduled job auto-expires past-due offers and notifies both parties.
Document room: reuse property_documents scoped by deal_id with
visibility_level=nda_stage, not a separate document-room table.
legal_matters status=issue_found blocks deals.stage from progressing to
closing (enforce as a state-machine guard, not just a UI warning) — reuse
the existing Dispute model/DisputeResolver for anything that becomes an
actual post-close dispute rather than handling disputes inside
legal_matters.

On deals.stage=completed: write to the existing Payment model with
purpose=transaction_commission (Part 1a) and process through the existing
GatewayFactory — no new payment path. Every stage transition writes to
AuditLog.
```

### MODULE: Distrax Escrow & Distrax Invest (P3 — stub only)
```
Do NOT implement real escrow or investment-product money movement — the
blueprint itself requires regulatory sign-off first. Scaffold only: a
feature-flagged "Escrow" tab on completed deals showing "Coming soon via
licensed partner"; a read-only "pooled opportunities" list with no
transacting capability, clearly labeled informational only. Note for
planning (don't build yet): a future escrow implementation would likely
extend the existing Wallet model rather than need a new ledger.
```

### Cross-cutting

#### 3.14 "Buying For" Personalization (P1)
```
Add users.buying_for (Part 1a). Wire into whatever post-registration
onboarding currently exists (check Auth/AuthController's redirect) rather
than building a separate onboarding flow. Options: My Home, Investment,
Fix & Flip, Development, Land Banking, Commercial Investment. Drives
default ranking weights (3.5), default Investment Potential view (3.9),
default dashboard widgets (3.15). Editable later from profile settings,
re-personalizing immediately on change.
```

#### 3.15 Personalized Investor Dashboard (P1)
```
Extend the existing user DashboardController (Website/) — don't build a
new dashboard route. Confirm current widgets first, then add: deal radar
mandates (extended SavedSearch), portfolio summary, open offers, upcoming
inspections (from VisitSchedule/inspections), verification coverage,
average discount discovered, potential rental yield, potential flip
margin. Follow the existing DashboardAggregator service pattern (already
used admin-side) for computing stats rather than ad hoc controller
queries. Every widget links through to the underlying list/page.
```

#### 3.16 Seller Reputation (P1)
```
Populate seller_profiles (or User columns, per Part 5 #1) with: verified
identity, response time, offer response rate, completed transactions,
professional profile (agent/developer), disclosure compliance. Response
time and offer response rate need new tracking (no existing analog).
"Buyer feedback after completed transactions" reuses the existing Review
model (reviews.deal_id, Part 1a) and VerifiedReviewGuard — don't build a
parallel feedback system. Never render reputation as an implied guarantee
(no "100% trustworthy") — show the underlying stats and let the buyer
read them.
```

#### 3.17 Ask Distrax AI (P2)
```
Build ask_distrax_queries as retrieval-grounded Q&A scoped to a single
property — new table, no existing analog. Answer ONLY from that
property's structured/verified data (verification_scores, disclosures,
risk_assessments, valuations, comparable_properties): the prompt to the
LLM includes only that property's records, and the model is instructed to
say "not available" rather than infer. Tag every claim
verified_fact/estimate/recommendation (store as answer_type or a
structured breakdown in cited_evidence). Support: why is this a deal, what
documents are verified, what are the disclosed risks, how does price
compare to similar properties, what to ask at inspection, what further
professional checks to consider. Log every query for audit/quality review.
```

#### 3.18 Admin & Verification Ops Console (P0)
```
Extend the existing admin panel (Admin/ controllers, permission
middleware, AdminAuditMiddleware, AdminMenu sidebar builder) — do not
scaffold a second admin system. New controllers
(VerificationCaseController, DealController, LegalMatterController, and
InspectionController only if Part 5 #2 goes against extending
VisitSchedule) follow the exact pattern of the existing ~40 Admin/
controllers. Register sidebar entries via AdminMenu, permissions via the
existing Permission/Role system, icons via AdminIcons. Extend
AdminGlobalSearch so verification cases/deals are searchable from the
global admin search, matching how listings/users already are. Roles
covered: verification_officer (task queue), legal_reviewer (title/
litigation/ownership queue), surveyor (survey queue), inspector
(inspection queue), moderator (listing/content flags), deal_manager
(offers/transaction board), finance (fees/commissions reconciled against
Payment), super_admin (rules, permissions, audit log viewer,
configuration). Enforce every role permission at the API layer
(policies/gates), not just by hiding UI.
```

#### 3.19 Institutional Bulk Disposal (P2)
```
Build institutional_accounts + bulk_upload_batches (new tables). Read
AgencyCsvImporter and PropertyListingCsvImporter's actual implementation
and build InstitutionalCsvImporter following the same structure (chunked
processing, validation, error reporting) rather than a new import
mechanism — every imported row still enters the normal verification_cases
flow, no institutional shortcut around verification. Build: a portfolio
dashboard scoped to the institutional account's listings, buyer-matching
against relevant deal_radar_rules/saved searches, a shared document room
(reuse property_documents), audit trail (existing AuditLog), and
transaction reporting export (reuse ExportManager/CsvStreamExporter).
```

---

## Part 4 — Global UI/UX Rules (flat QA checklist)

- **Verification badge** — always the single source-of-truth component (3.2); never a screen-local color decision.
- **Property View Page order** is fixed (3.6) — hero → score/badge → price/discount → urgency → sale reason (privacy-safe) → passport → deal analysis → risk → disclosures → specs → location → investment potential → price history → timeline → document vault → comparables → inspection → offers → Ask Distrax → decision summary → sticky CTA.
- **Every model-generated number** (valuation, exit value, Deal Score components, calculator outputs) is labeled as an estimate with a confidence indicator — never presented as fact.
- **Every risk row** shown even when Low, with an expandable "why" + evidence link — no risk without an explanation.
- **Distress reason** shown only per the seller's chosen visibility (public / disclosure-only / private); the raw category never leaks to a buyer-facing screen when set private.
- **Sensitive documents/evidence** gated by permission, never exposed to anonymous or unverified users regardless of which screen requests them.
- **Sponsored listings** clearly labeled, can't override the verification-status trust gate.
- **Timeline/history** — unknown history shown as explicitly unavailable, never fabricated or inferred.
- **Offer/negotiation privacy** — timeline and comparables show offer activity only as aggregates, never competing buyers' identities or exact amounts.
- **Ask Distrax answers** — every claim tagged verified fact / estimate / recommendation; never invents facts outside the property's stored records.
- **Deal Score** — always shown with its breakdown available, never a bare number.

---

## Part 5 — Open Decisions

1. **Seller identity**: `seller_profiles` new table, or columns on `users`? Resolve via the audit prompt reading OwnerListingController/AgencyController's current pattern.
2. **Inspection vs VisitSchedule**: resolve by reading VisitSchedule's actual columns (audit prompt, question 2).
3. **Watchlist vs Favorite**: resolve by reading Favorite's actual columns/usage (audit prompt, question 3).
4. **Price history/timeline vs AuditLog**: check whether AuditLog already gives you this for free before building two more tables (audit prompt, question 4).
5. **"Limited visibility" for Verification in Progress** — undefined in the source blueprint. I proposed a default gate in 3.2 (no price/sqm comps, no "make offer"); confirm or change before the agent builds the gating logic.
6. **"Qualified buyer" for full evidence access** — undefined in the source blueprint. I proposed `kyc_status=verified AND active NDA/offer` as a placeholder; confirm the real rule before shipping, this is an access-control decision not a cosmetic one.
7. **Deal Score weighting** — no numeric weights exist in the source doc, only the component list. Someone (you or a pricing/data person) sets and periodically tunes these; the agent can build the structure but not invent defensible weights.
8. **Escrow/Invest** — explicitly needs regulatory sign-off per the source doc. Don't let an agent "helpfully" wire up real payments here without that sign-off, even though Wallet already exists and would make it technically easy.
