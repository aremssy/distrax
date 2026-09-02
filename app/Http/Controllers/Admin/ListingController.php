<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PropertyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkApproveListingsRequest;
use App\Http\Requests\Admin\BulkTrashListingsRequest;
use App\Http\Requests\Admin\StoreListingRequest;
use App\Http\Requests\Admin\UpdateListingRequest;
use App\Models\CustomField;
use App\Models\Currency;
use App\Models\PropertyImage;
use App\Models\PropertyListing;
use App\Models\PropertyVideo;
use App\Models\User;
use App\Models\Zone;
use App\Services\ListingCsvExporter;
use App\Services\NotificationCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListingController extends Controller
{
    private const SORTABLE = ['zone', 'type', 'price', 'status', 'owner', 'published'];

    public function index(Request $request): View
    {
        // The table renders a single thumbnail per row: the cover image, else the first image.
        $query = PropertyListing::with([
            'zone:id,name',
            'owner:id,name,email',
            'coverImage',
            'images' => fn ($q) => $q->orderBy('sort_order')->limit(1),
        ])->withCount('reports');

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $query->filter($request);

        $this->applySort($query, $request);

        $listings = $query->paginate(20)->withQueryString();
        $zones = Zone::active()->orderBy('name')->get(['id', 'name']);
        $owners = User::whereIn('id', PropertyListing::withTrashed()->select('owner_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $freshnessDays = (int) setting('listing_freshness_days', 30);

        return view('admin.listings.index', compact('listings', 'zones', 'owners', 'freshnessDays'));
    }

    private function applySort($query, Request $request): void
    {
        $sort = $request->string('sort')->value();
        $direction = $request->string('dir')->value() === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, self::SORTABLE, true)) {
            $query->latest();

            return;
        }

        match ($sort) {
            'zone' => $query->orderBy(Zone::select('name')->whereColumn('zones.id', 'property_listings.zone_id'), $direction),
            'owner' => $query->orderBy(User::select('name')->whereColumn('users.id', 'property_listings.owner_id'), $direction),
            'published' => $query->orderBy('published_at', $direction),
            default => $query->orderBy($sort, $direction),
        };

        $query->orderByDesc('id');
    }

    /**
     * Stream the filtered listing set as a CSV download.
     */
    public function export(Request $request, ListingCsvExporter $exporter): StreamedResponse
    {
        $query = PropertyListing::with(['zone:id,name', 'owner:id,name,email']);

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $query->filter($request);
        $this->applySort($query, $request);

        return $exporter->export($query);
    }

    /**
     * Approve all pending listings in the selection.
     */
    public function bulkApprove(BulkApproveListingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $count = PropertyListing::whereIn('id', $validated['ids'])
            ->where('status', 'pending')
            ->update(['status' => 'active', 'published_at' => now()]);

        return back()->with('success', "{$count} listing(s) approved.");
    }

    /**
     * Move the selected listings to trash.
     */
    public function bulkTrash(BulkTrashListingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $count = PropertyListing::whereIn('id', $validated['ids'])->delete();

        return back()->with('success', "{$count} listing(s) moved to trash.");
    }

    public function create(Request $request): View
    {
        return view('admin.listings.create', $this->formData(
            new PropertyListing([
                'type' => $request->string('type', 'rent')->value(),
                'language_tag' => setting('default_language', 'en'),
                'allowed_for' => 'both',
                'status' => 'pending',
            ])
        ));
    }

    public function store(StoreListingRequest $request): RedirectResponse
    {
        $listing = DB::transaction(function () use ($request): PropertyListing {
            $listing = PropertyListing::create($this->listingData($request->validated()));

            $this->syncCustomFieldValues($listing, $request->input('custom_fields', []));
            $this->storeImages($listing, $request->file('images', []));
            $this->storeFloorPlans($listing, $request->file('floor_plans', []));
            $this->storeVideos($listing, $request->input('videos', []));
            $this->storeVideoFiles($listing, $request->file('video_files', []));

            return $listing;
        });

        return redirect()->route('admin.listings.show', $listing)->with('success', 'Listing created.');
    }

    public function show(int $id): View
    {
        $listing = PropertyListing::withTrashed()
            ->with([
                'zone',
                'owner',
                'agency',
                'images',
                'videos',
                'customFieldValues.field',
                'reports' => fn ($q) => $q->with('reporter:id,name,email')->latest()->limit(10),
            ])
            ->findOrFail($id);

        $freshnessDays = (int) setting('listing_freshness_days', 30);

        return view('admin.listings.show', compact('listing', 'freshnessDays'));
    }

    public function edit(PropertyListing $listing): View
    {
        $listing->load(['customFieldValues', 'images', 'videos']);

        return view('admin.listings.edit', $this->formData($listing));
    }

    public function update(UpdateListingRequest $request, PropertyListing $listing): RedirectResponse
    {
        DB::transaction(function () use ($request, $listing): void {
            $listing->update($this->listingData($request->validated(), $listing));

            $this->syncCustomFieldValues($listing, $request->input('custom_fields', []));
            $this->storeImages($listing, $request->file('images', []));
            $this->storeFloorPlans($listing, $request->file('floor_plans', []));
            $this->storeVideos($listing, $request->input('videos', []));
            $this->storeVideoFiles($listing, $request->file('video_files', []));
        });

        return redirect()->route('admin.listings.show', $listing)->with('success', 'Listing updated.');
    }

    public function approve(PropertyListing $listing, NotificationCenter $notifications): RedirectResponse
    {
        $listing->update([
            'status' => 'active',
            'needs_confirmation_at' => null,
            'published_at' => $listing->published_at ?? now(),
        ]);

        $notifications->announce(
            type: 'listing_pending_approval',
            adminTitle: 'Listing approved',
            user: $listing->owner,
            userTitle: 'Your listing was approved',
            body: $listing->title,
            adminLink: route('admin.listings.show', $listing),
            subject: $listing,
            permission: 'listings.view',
        );

        return back()->with('success', 'Listing approved.');
    }

    public function reject(Request $request, PropertyListing $listing, NotificationCenter $notifications): RedirectResponse
    {
        $listing->update(['status' => 'rejected']);

        $notifications->announce(
            type: 'listing_pending_approval',
            adminTitle: 'Listing rejected',
            user: $listing->owner,
            userTitle: 'Your listing was rejected',
            body: $listing->title,
            adminLink: route('admin.listings.show', $listing),
            subject: $listing,
            permission: 'listings.view',
        );

        return back()->with('success', 'Listing rejected.');
    }

    public function feature(PropertyListing $listing): RedirectResponse
    {
        $listing->update(['is_featured' => ! $listing->is_featured]);
        $state = $listing->is_featured ? 'featured' : 'unfeatured';

        return back()->with('success', "Listing {$state}.");
    }

    public function verify(PropertyListing $listing): RedirectResponse
    {
        $listing->update(['is_verified' => ! $listing->is_verified]);
        $state = $listing->is_verified ? 'verified' : 'unverified';

        return back()->with('success', "Listing {$state}.");
    }

    public function archive(PropertyListing $listing): RedirectResponse
    {
        $listing->update([
            'status' => 'archived',
            'needs_confirmation_at' => null,
        ]);

        return back()->with('success', 'Listing archived.');
    }

    public function restore(int $id): RedirectResponse
    {
        $listing = PropertyListing::onlyTrashed()->findOrFail($id);
        $listing->restore();

        return redirect()->route('admin.listings.index')->with('success', 'Listing restored.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $listing = PropertyListing::withTrashed()->findOrFail($id);

        if ($listing->trashed()) {
            $listing->forceDelete();
            $msg = 'Listing permanently deleted.';
        } else {
            $listing->delete();
            $msg = 'Listing moved to trash.';
        }

        return redirect()->route('admin.listings.index')->with('success', $msg);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(PropertyListing $listing): array
    {
        return [
            'listing' => $listing,
            'types' => PropertyType::options(),
            'zones' => Zone::active()->orderBy('name')->get(['id', 'name']),
            'owners' => User::orderBy('name')->get(['id', 'name', 'email']),
            'customFieldsByType' => CustomField::active()
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get()
                ->groupBy('property_type'),
            'customFieldValues' => $listing->exists
                ? $listing->customFieldValues->pluck('value', 'custom_field_id')->all()
                : [],
            'currencies' => Currency::activeCached(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function listingData(array $validated, ?PropertyListing $listing = null): array
    {
        unset(
            $validated['custom_fields'],
            $validated['images'],
            $validated['floor_plans'],
            $validated['videos'],
            $validated['video_files'],
        );

        $validated['service_charge'] = $validated['service_charge'] ?? 0;
        $validated['advance_months'] = $validated['advance_months'] ?? 0;
        $validated['is_negotiable'] = (bool) ($validated['is_negotiable'] ?? false);
        $validated['parking'] = (bool) ($validated['parking'] ?? false);
        $validated['furnished'] = (bool) ($validated['furnished'] ?? false);
        $validated['is_featured'] = (bool) ($validated['is_featured'] ?? false);
        $validated['is_verified'] = (bool) ($validated['is_verified'] ?? false);

        if (($validated['status'] ?? null) === 'active' && ! $listing?->published_at) {
            $validated['published_at'] = now();
        }

        if (($validated['status'] ?? null) !== 'active') {
            $validated['needs_confirmation_at'] = null;
        }

        return $validated;
    }

    /**
     * @param  array<int|string, mixed>  $values
     */
    private function syncCustomFieldValues(PropertyListing $listing, array $values): void
    {
        $fieldIds = CustomField::forType($listing->type)
            ->active()
            ->pluck('id');

        foreach ($fieldIds as $fieldId) {
            $value = $values[$fieldId] ?? null;
            $value = is_array($value)
                ? implode(', ', array_filter($value, fn ($item) => filled($item)))
                : $value;

            $listing->customFieldValues()->updateOrCreate(
                ['custom_field_id' => $fieldId],
                ['value' => filled($value) ? (string) $value : null]
            );
        }

        $listing->customFieldValues()
            ->whereNotIn('custom_field_id', $fieldIds)
            ->delete();
    }

    /**
     * @param  array<int, UploadedFile>  $images
     */
    private function storeImages(PropertyListing $listing, array $images): void
    {
        $disk = $this->mediaDisk();
        $nextSortOrder = (int) $listing->images()->max('sort_order') + 1;

        foreach ($images as $image) {
            if (! $image instanceof UploadedFile) {
                continue;
            }

            $listing->images()->create([
                'path' => $image->store("listings/{$listing->id}", $disk),
                'disk' => $disk,
                // The first non-floor-plan photo becomes the cover.
                'is_cover' => ! $listing->galleryImages()->exists(),
                'sort_order' => $nextSortOrder++,
            ]);
        }
    }

    /**
     * @param  array<int, mixed>  $floorPlans
     */
    private function storeFloorPlans(PropertyListing $listing, array $floorPlans): void
    {
        $disk = $this->mediaDisk();
        $nextSortOrder = (int) $listing->floorPlanImages()->max('sort_order') + 1;

        foreach ($floorPlans as $floorPlan) {
            if (! $floorPlan instanceof UploadedFile) {
                continue;
            }

            $listing->images()->create([
                'path' => $floorPlan->store("listings/{$listing->id}/floor-plans", $disk),
                'disk' => $disk,
                'is_cover' => false,
                'is_floor_plan' => true,
                'sort_order' => $nextSortOrder++,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $videos
     */
    private function storeVideos(PropertyListing $listing, array $videos): void
    {
        $nextSortOrder = (int) $listing->videos()->max('sort_order') + 1;

        foreach ($videos as $video) {
            $url = trim((string) ($video['url'] ?? ''));

            // Skip blanks and links already attached, so re-saving the form
            // never duplicates a video.
            if ($url === '' || $listing->videos()->where('url', $url)->exists()) {
                continue;
            }

            $listing->videos()->create([
                'url' => $url,
                'source' => PropertyVideo::detectSource($url) ?? 'upload',
                'sort_order' => $nextSortOrder++,
            ]);
        }
    }

    /**
     * @param  array<int, mixed>  $videoFiles
     */
    private function storeVideoFiles(PropertyListing $listing, array $videoFiles): void
    {
        $disk = $this->mediaDisk();
        $nextSortOrder = (int) $listing->videos()->max('sort_order') + 1;

        foreach ($videoFiles as $videoFile) {
            if (! $videoFile instanceof UploadedFile) {
                continue;
            }

            $path = $videoFile->store("listings/{$listing->id}/videos", $disk);

            $listing->videos()->create([
                // Relative for the public disk so playback never depends on APP_URL.
                'url' => $disk === 'public' ? '/storage/'.$path : Storage::disk($disk)->url($path),
                'path' => $path,
                'disk' => $disk,
                'source' => 'upload',
                'sort_order' => $nextSortOrder++,
            ]);
        }
    }

    public function destroyImage(PropertyListing $listing, PropertyImage $image): JsonResponse
    {
        if ($image->disk !== 'remote' && ! Str::startsWith($image->path, ['http://', 'https://'])) {
            Storage::disk($image->disk)->delete($image->path);
        }

        $wasCover = $image->is_cover;
        $image->delete();

        // Keep the listing with a cover photo when its current cover is deleted.
        if ($wasCover) {
            $listing->galleryImages()->orderBy('sort_order')->first()?->update(['is_cover' => true]);
        }

        return response()->json(['deleted' => true]);
    }

    public function destroyVideo(PropertyListing $listing, PropertyVideo $video): JsonResponse
    {
        if ($video->path && $video->disk) {
            Storage::disk($video->disk)->delete($video->path);
        }

        $video->delete();

        return response()->json(['deleted' => true]);
    }

    private function mediaDisk(): string
    {
        $disk = (string) setting('storage_driver', config('filesystems.default', 'local'));

        // The "local" disk is private (storage/app/private) and has no public URL,
        // so listing media stored there can never be displayed. Serve it from the
        // public disk instead.
        if ($disk === 'local' || ! Config::has("filesystems.disks.{$disk}")) {
            return 'public';
        }

        return $disk;
    }
}
