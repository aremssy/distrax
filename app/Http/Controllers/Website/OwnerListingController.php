<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Listing\StoreListingRequest;
use App\Http\Requests\Api\V1\Listing\UpdateListingRequest;
use App\Http\Requests\Api\V1\Listing\UpdateListingStatusRequest;
use App\Http\Requests\Api\V1\Listing\UploadMediaRequest;
use App\Models\Currency;
use App\Models\CustomField;
use App\Models\ListingPackage;
use App\Models\PropertyImage;
use App\Models\PropertyListing;
use App\Models\Zone;
use App\Services\ListingIntakeService;
use App\Services\PostEntitlementService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class OwnerListingController extends Controller
{
    public function index(Request $request, PostEntitlementService $entitlements): View
    {
        $statusFilter = $request->string('status')->value();
        $statuses = ['draft', 'pending', 'active', 'rented', 'sold', 'archived', 'rejected'];

        if (! in_array($statusFilter, $statuses, true)) {
            $statusFilter = null;
        }

        $ownedListings = PropertyListing::query()->whereBelongsTo($request->user(), 'owner');

        $statusCounts = (clone $ownedListings)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $listings = (clone $ownedListings)
            ->with(['zone:id,name', 'coverImage:id,property_listing_id,path,disk,is_cover,sort_order'])
            ->withCount(['favorites', 'visitSchedules', 'contactReveals', 'conversations'])
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = $entitlements->summary($request->user());

        $totalViews = (int) PropertyListing::whereBelongsTo($request->user(), 'owner')->sum('view_count');
        $totalLeads = (int) $listings->sum('visit_schedules_count') + (int) $listings->sum('contact_reveals_count') + (int) $listings->sum('conversations_count');
        $analytics = [
            'total_views' => $totalViews,
            'total_leads' => $totalLeads,
            'conversion_rate' => $totalViews > 0 ? round(($totalLeads / $totalViews) * 100, 1) : 0.0,
        ];

        $boostPackage = ListingPackage::where('is_active', true)->get()
            ->first(fn (ListingPackage $package) => $package->features['bump_up'] ?? false);

        return view('website.owner.listings.index', compact('listings', 'summary', 'analytics', 'statusCounts', 'statusFilter', 'statuses', 'boostPackage'));
    }

    public function create(Request $request, PostEntitlementService $entitlements): View|RedirectResponse
    {
        if (! $entitlements->canUserPost($request->user())) {
            return redirect()->route('owner.listings.index')->withErrors(['limit' => 'Your listing limit has been reached. Upgrade your plan or buy more posts.']);
        }

        return view('website.owner.listings.form', [
            'listing' => null,
            'zones' => $this->zones(),
            'amenities' => config('amenities'),
            'customFields' => $this->customFields(),
            'currencies' => Currency::activeCached(),
        ]);
    }

    public function store(StoreListingRequest $request, PostEntitlementService $entitlements, ListingIntakeService $intake): RedirectResponse
    {
        $this->authorize('create', PropertyListing::class);

        $isDraft = $this->wantsDraft($request);

        // Drafts never go public, so they are free; only a real submission needs quota.
        if (! $isDraft && ! $entitlements->canUserPost($request->user())) {
            return back()->withErrors(['limit' => 'Your listing limit has been reached.'])->withInput();
        }

        $listing = PropertyListing::create([
            ...$this->listingAttributes($request),
            'owner_id' => $request->user()->id,
            'status' => $isDraft ? 'draft' : 'pending',
        ]);

        if ($request->has('custom_fields')) {
            $this->syncCustomFieldValues($listing, $request->validated('custom_fields') ?? []);
        }

        $this->storeUploadedMedia($request, $listing);
        $intake->applySellerIdentity($request->user(), $request->validated(), $request->file('poa_document'));
        $intake->storeTitleDocuments($listing, $request->file('title_documents', []), $request->user());

        return $isDraft
            ? redirect()->route('owner.listings.edit', $listing)->with('success', 'Draft saved. You can finish and publish it any time.')
            : redirect()->route('owner.listings.index')->with('success', 'Listing submitted for review.');
    }

    public function edit(PropertyListing $listing): View
    {
        $this->authorize('update', $listing);
        $listing->load(['galleryImages', 'floorPlanImages', 'customFieldValues']);

        return view('website.owner.listings.form', [
            'listing' => $listing,
            'zones' => $this->zones(),
            'amenities' => config('amenities'),
            'customFields' => $this->customFields($listing->type),
            'currencies' => Currency::activeCached(),
        ]);
    }

    public function update(UpdateListingRequest $request, PropertyListing $listing, PostEntitlementService $entitlements, ListingIntakeService $intake): RedirectResponse
    {
        $this->authorize('update', $listing);

        $data = $this->listingAttributes($request);

        // Publishing a draft is the point it becomes a real listing, so the quota is
        // checked here rather than when the draft was first saved.
        if ($listing->status === 'draft' && ! $this->wantsDraft($request)) {
            if (! $entitlements->canUserPost($request->user())) {
                return back()->withErrors(['limit' => 'Your listing limit has been reached.'])->withInput();
            }

            $data['status'] = 'pending';
        }

        $listing->update($data);

        // A partial update (e.g. only the title) must not wipe existing custom values.
        if ($request->has('custom_fields')) {
            $this->syncCustomFieldValues($listing, $request->validated('custom_fields') ?? []);
        }

        $this->storeUploadedMedia($request, $listing);
        $intake->applySellerIdentity($request->user(), $request->validated(), $request->file('poa_document'));
        $intake->storeTitleDocuments($listing, $request->file('title_documents', []), $request->user());

        return $listing->status === 'pending' && $listing->wasChanged('status')
            ? redirect()->route('owner.listings.index')->with('success', 'Listing submitted for review.')
            : back()->with('success', 'Listing updated successfully.');
    }

    private function wantsDraft(Request $request): bool
    {
        return $request->input('save_action') === 'draft';
    }

    /**
     * The listing column values from the request, without relation/media keys.
     *
     * Null values are dropped so NOT NULL columns (e.g. service_charge) fall
     * back to their database defaults instead of failing the insert, and so a
     * partial update never wipes an unrelated field the form left blank.
     *
     * @return array<string, mixed>
     */
    private function listingAttributes(StoreListingRequest|UpdateListingRequest $request): array
    {
        $data = Arr::except($request->validated(), [
            'custom_fields', 'images', 'floor_plans',
            ...ListingIntakeService::NON_LISTING_KEYS,
        ]);

        return array_filter($data, static fn ($value): bool => $value !== null);
    }

    /** Photos and floor plans can be attached while the listing is being created. */
    private function storeUploadedMedia(Request $request, PropertyListing $listing): void
    {
        $nextOrder = (int) $listing->images()->max('sort_order') + 1;

        foreach ($request->file('images', []) as $image) {
            PropertyImage::create([
                'property_listing_id' => $listing->id,
                'path' => $image->store('properties/'.$listing->id, 'public'),
                'disk' => 'public',
                'is_cover' => ! $listing->galleryImages()->exists(),
                'sort_order' => $nextOrder++,
            ]);
        }

        foreach ($request->file('floor_plans', []) as $floorPlan) {
            PropertyImage::create([
                'property_listing_id' => $listing->id,
                'path' => $floorPlan->store('properties/'.$listing->id.'/floor-plans', 'public'),
                'disk' => 'public',
                'is_cover' => false,
                'is_floor_plan' => true,
                'sort_order' => $nextOrder++,
            ]);
        }
    }

    public function uploadMedia(UploadMediaRequest $request, PropertyListing $listing): RedirectResponse
    {
        $this->authorize('uploadMedia', $listing);
        $startOrder = (int) $listing->images()->max('sort_order') + 1;
        foreach ($request->file('images', []) as $index => $image) {
            PropertyImage::create(['property_listing_id' => $listing->id, 'path' => $image->store('properties/'.$listing->id, 'public'), 'disk' => 'public', 'is_cover' => $listing->images()->count() === 0 && $index === 0, 'sort_order' => $startOrder + $index]);
        }

        return back()->with('success', 'Property photos uploaded.');
    }

    public function updateStatus(UpdateListingStatusRequest $request, PropertyListing $listing): RedirectResponse
    {
        $this->authorize('updateStatus', $listing);
        $listing->update(['status' => $request->validated('status')]);

        return back()->with('success', 'Listing status updated.');
    }

    public function confirmFreshness(PropertyListing $listing): RedirectResponse
    {
        $this->authorize('confirmFreshness', $listing);
        $listing->update(['last_freshness_check_at' => now(), 'needs_confirmation_at' => now()->addDays((int) setting('listing_freshness_days', 30))]);

        return back()->with('success', 'Listing availability confirmed.');
    }

    /** @return Collection<int, Zone> */
    private function zones(): Collection
    {
        return Zone::query()->active()->whereIn('type', ['city', 'area'])->orderBy('name')->get(['id', 'name', 'type']);
    }

    /**
     * Admin-defined extra fields for the listing form (Kitchen, Balcony, and any
     * others configured for this property type, plus the `all` type fields).
     *
     * @return Collection<int, CustomField>
     */
    private function customFields(?string $type = null): Collection
    {
        return CustomField::query()
            ->active()
            ->forType($type ?? 'rent')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /** @param  array<int|string, mixed>  $values */
    private function syncCustomFieldValues(PropertyListing $listing, array $values): void
    {
        $fieldIds = CustomField::query()->active()->forType($listing->type)->pluck('id');

        $rows = $fieldIds->map(function (int $fieldId) use ($listing, $values): array {
            $value = $values[$fieldId] ?? null;
            $value = is_array($value)
                ? implode(', ', array_filter($value, fn ($item) => filled($item)))
                : $value;

            return [
                'property_listing_id' => $listing->id,
                'custom_field_id' => $fieldId,
                'value' => filled($value) ? (string) $value : null,
            ];
        });

        if ($rows->isNotEmpty()) {
            $listing->customFieldValues()->upsert($rows->all(), ['property_listing_id', 'custom_field_id'], ['value']);
        }

        $listing->customFieldValues()->whereNotIn('custom_field_id', $fieldIds)->delete();
    }
}
