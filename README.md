# Rentdo

A zone-based property rental & sale marketplace with a technician-hiring marketplace, short-stay (hotel) booking, and a subscription-based rent-management suite — built on Laravel 13, Tailwind CSS, and MySQL. One backend powers three surfaces: the **Admin Panel** (Blade), the **REST API** (`/api/v1`, Sanctum), and the **Website** (SEO-friendly, responsive, guest + logged-in dashboard).

See `SRS.md` for the full functional specification.

## Requirements

- PHP 8.3+ (developed against 8.4)
- MySQL 8+ (or another Laravel-supported database)
- Composer 2
- Node.js 18+ and npm (for building frontend assets)
- PHP extensions: bcmath, ctype, curl, dom, fileinfo, gd, intl, json, mbstring, openssl, pdo, pdo_mysql, tokenizer, xml, zip
- Shared/cPanel hosting is supported — no Node/Redis is required in production once assets are built.

## Installation

### Option A — Guided installer (recommended for buyers)

1. Upload the codebase to your server and point your web root at `public/`.
2. Run `composer install --no-dev --optimize-autoloader` and `npm install && npm run build`.
3. Visit the site in a browser — you'll be redirected to `/install`, a step-by-step wizard that:
   - Checks PHP version, required extensions, and writable storage folders.
   - Writes your database credentials to `.env`, generates `APP_KEY`, and runs migrations + seeders.
   - Creates your Main Admin account.
   - Collects basic settings (site name, default language, default currency).
   - Verifies your Envato purchase code (see **Licensing** below).
4. Once finished, the installer writes `storage/app/installed.lock` and the app is live. Re-visiting `/install` afterwards redirects straight to the admin dashboard.

### Option B — Manual / local development

```bash
composer install
cp .env.example .env
php artisan key:generate
# configure DB_* in .env, then:
php artisan migrate --seed
npm install
npm run build   # or `npm run dev` while developing
```

`composer run dev` starts the app server, queue listener, log tailer (Pail), and Vite dev server together.

## Configuration

Most operational settings (branding, languages, currencies, payment gateway keys, SMS/OTP provider, storage driver, post limits, plan features, etc.) are configured **from the Admin Panel**, not `.env` — this is intentional so a buyer can fully re-brand and re-configure the product without touching code. `.env` only needs:

| Variable | Purpose |
|---|---|
| `APP_URL`, `APP_KEY` | Standard Laravel app config. |
| `DB_*` | Database connection. |
| `FILESYSTEM_DISK` | `local` by default; set to `s3` (with `AWS_*` vars) for S3-backed media storage. |
| `QUEUE_CONNECTION` | `database` by default. A queue worker (`php artisan queue:work`) is required for notification dispatch, exports, and campaign sending. |
| `BROADCAST_CONNECTION` | Defaults to `log` (chat/notification events are logged, not pushed). Install and configure Reverb, Pusher, or Ably for real-time chat/notifications in production. |
| `ENVATO_PERSONAL_TOKEN`, `ENVATO_API_URL` | Envato license verification — see **Licensing**. |
| `INSTALLER_ALLOW_MANUAL_ACTIVATION` | Opt-in escape hatch for local/demo installs without an Envato token — see **Licensing**. |
| `BACKUP_ARCHIVE_PASSWORD` | Optional password to encrypt scheduled backup archives. |

### Licensing

License verification runs against the Envato API using `ENVATO_PERSONAL_TOKEN`. **If this token is not configured, license verification fails closed** — no purchase code is auto-accepted. For local development or demo deployments without a real Envato token, explicitly set `INSTALLER_ALLOW_MANUAL_ACTIVATION=true`; the activation record will be flagged `manual_override` for auditability. Never enable this flag on a production/customer-facing install.

### Queue worker & scheduler

Run a persistent queue worker in production:

```bash
php artisan queue:work --tries=3
```

And register the Laravel scheduler in your server's crontab (works on shared/cPanel hosting):

```
* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler drives:

| Command | Schedule | Purpose |
|---|---|---|
| `backup:clean` | 01:00 daily | Prune backups older than the configured retention window. |
| `backup:run` | 01:15 daily | Full application + database backup (see **Backups**). |
| `backup:monitor` | 01:45 daily | Health-check backups and notify if unhealthy/stale. |
| `listings:flag-stale` | 02:00 daily | Flag listings not refreshed within the configured freshness window. |
| `subscriptions:expire` | 02:15 daily | Expire subscriptions past their `ends_at`. |
| `sitemap:generate` | 03:00 daily | Regenerate `sitemap.xml` for SEO. |
| `rent:send-payment-reminders` | 08:00 daily | Flag overdue rent payments and notify tenants/owners with the `rent_reminders` feature unlocked. |

### Storage

Uploaded media (property photos, verification documents, agency logos, etc.) is stored via Laravel's filesystem abstraction. Switch `FILESYSTEM_DISK` to `s3` and set the `AWS_*` variables to move storage off the local disk — no code changes needed.

### Backups

Backups are powered by [spatie/laravel-backup](https://spatie.be/docs/laravel-backup), configured in `config/backup.php` to archive the application (excluding `vendor`, `node_modules`, `.git`, and framework cache) plus a database dump, stored on the `local` disk under `storage/app/private`. Useful commands:

```bash
php artisan backup:run            # run a backup now
php artisan backup:list           # list existing backups and disk health
php artisan backup:clean          # prune backups per the retention policy in config/backup.php
```

Configure backup destinations (S3, etc.) and notification recipients in `config/backup.php`. Restoring is a matter of unzipping the archive and importing the included database dump — no bespoke restore tooling is required.

### Payment gateways

Stripe and PayPal are required; bKash, Razorpay, and Paystack are supported optional gateways. Enable/configure gateways and their API keys from **Admin Panel → Payments & Finance → Settings**; the `active_payment_gateways` setting controls which are offered to customers. Each gateway's webhook endpoint is `POST /api/v1/webhooks/{gateway}` (e.g. `/api/v1/webhooks/stripe`) — register this URL in your gateway's dashboard. Card/payment data is never sent through this API; gateways are integrated client-side (Stripe.js / Razorpay Checkout) or via hosted redirect (PayPal, bKash, Paystack).

### SMS (OTP codes and notifications)

One driver powers both phone-verification codes and SMS notifications, configured at **Admin Panel → Settings → SMS**:

| Driver | Behaviour |
|---|---|
| `log` (default) | **Does not send anything.** Messages are written to the application log. Local development only. |
| `twilio` | Sends via Twilio. Requires Account SID, Auth Token, and a From number. |
| `vonage` | Sends via Vonage (Nexmo). Requires API key and secret. |

Leaving the driver on `log` in production means users never receive their verification codes, so set a real provider before going live. OTP delivery fails loudly if the provider rejects the message; notification SMS is best-effort and never blocks the in-app notification.

### Push notifications (Firebase)

Push uses the **FCM HTTP v1 API**. In the Firebase console go to *Project settings → Service accounts → Generate new private key*, then paste the whole downloaded JSON into **Admin Panel → Settings → SMS → Push Notifications**. Leave it blank to disable push.

The legacy FCM "server key" is no longer accepted by Google and is not used. Device tokens that Firebase reports as unregistered are pruned automatically.

### Localization

Languages (with RTL support) and currencies are managed from **Admin Panel → White-label & Settings**. The default language is English; add additional languages and translate strings from the built-in string editor — no `.env` change required.

## API documentation

Full REST API documentation (Scribe-generated) is available at `/docs` once the app is running, including an OpenAPI spec and a Postman collection (`public/docs/openapi.yaml`, `public/docs/collection.json`). Regenerate after adding/changing endpoints:

```bash
php artisan scribe:generate
```

## Updating

Upload the new release files over the old ones, then:

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan app:update --release=1.1.0
```

`app:update` is the safe path and does the rest for you, in order:

1. **Refuses to start** if a previous update never finished, so you can't stack an update on an unknown state (override with `--force` once you've restored a good state).
2. **Verifies file integrity** against `release-manifest.json` (a `path => sha256` map shipped with the release), catching a partial or corrupted upload *before* anything is migrated. Releases without a manifest skip this step.
3. **Takes a database backup** (`--skip-backup` to opt out — not recommended).
4. Puts the site into maintenance mode, runs migrations, rebuilds the caches, and brings it back up.
5. On failure: clears any half-built cache, leaves the site usable, keeps the "interrupted" marker, and tells you which backup to restore.

The target version flag is `--release`, **not** `--version` — Symfony Console reserves `--version` globally, so a command that declares it never runs.

Manual equivalent, if you prefer to drive each step yourself:

```bash
php artisan backup:run
php artisan down
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
php artisan up
```

Migrations in this codebase are additive/non-destructive by convention.

## Testing

```bash
composer test
# or, to filter:
php artisan test --compact --filter=SomeTest
```

Code style is enforced with Pint:

```bash
vendor/bin/pint
```

## Troubleshooting

- **"Unable to locate file in Vite manifest"** — run `npm run build` (or `npm run dev` during local development).
- **Installer redirects straight to the dashboard** — the app is already activated; delete `storage/app/installed.lock` and clear the `activations` table only if you intend to reset a local/dev install (never on a customer's live data).
- **Scheduled jobs not running** — confirm the server crontab entry above is installed and `php artisan schedule:list` shows the expected commands.
- **Chat/notifications not real-time** — `BROADCAST_CONNECTION` defaults to `log`; configure Reverb, Pusher, or Ably for live delivery.
- **Payment webhook signature failures** — verify the webhook secret configured in the Admin Panel matches the one registered with the gateway, and that your server's clock is in sync (NTP) since several gateways reject signatures with stale timestamps.

## License

This is a commercial CodeCanyon product. See your Envato purchase license for usage terms.
