<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Chat\StartConversationRequest;
use App\Http\Requests\Api\V1\Engagement\StoreSavedSearchRequest;
use App\Http\Requests\Api\V1\Visit\StoreVisitRequest;
use App\Models\Block;
use App\Models\Compare;
use App\Models\Conversation;
use App\Models\Favorite;
use App\Models\Message;
use App\Models\PropertyListing;
use App\Models\SavedSearch;
use App\Models\VisitSchedule;
use App\Services\ContactRevealService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EngagementController extends Controller
{
    /** Maximum listings allowed in a single compare set. */
    private const MAX_COMPARE = 5;

    public function index(Request $request): View
    {
        $favorites = PropertyListing::query()
            ->join('favorites', 'favorites.property_listing_id', '=', 'property_listings.id')
            ->where('favorites.user_id', $request->user()->id)
            ->where('property_listings.status', 'active')
            ->with(['zone:id,name', 'owner:id,name,avatar', 'coverImage:id,property_listing_id,path,disk,is_cover,sort_order'])
            ->withViewerFavorite($request->user())
            ->select('property_listings.*')
            ->orderByDesc('favorites.created_at')
            ->paginate(9, ['*'], 'favorites_page');

        $savedSearches = $request->user()->savedSearches()->latest()->get();

        return view('website.dashboard.wishlist', compact('favorites', 'savedSearches'));
    }

    public function storeFavorite(Request $request, PropertyListing $property): RedirectResponse
    {
        abort_unless($property->status === 'active', 404);
        Favorite::firstOrCreate(['user_id' => $request->user()->id, 'property_listing_id' => $property->id]);

        return back()->with('success', 'Property added to your favorites.');
    }

    public function destroyFavorite(Request $request, PropertyListing $property): RedirectResponse
    {
        Favorite::query()->whereBelongsTo($request->user())->where('property_listing_id', $property->id)->delete();

        return back()->with('success', 'Property removed from your favorites.');
    }

    public function storeSavedSearch(StoreSavedSearchRequest $request): RedirectResponse
    {
        if ($request->user()->savedSearches()->count() >= 20) {
            return back()->withErrors(['saved_search' => 'You can save up to 20 searches.']);
        }

        $request->user()->savedSearches()->create($request->safe()->only(['name', 'criteria', 'alert_on']));

        return back()->with('success', 'Search saved successfully.');
    }

    public function destroySavedSearch(Request $request, SavedSearch $savedSearch): RedirectResponse
    {
        abort_unless($savedSearch->user_id === $request->user()->id, 403);
        $savedSearch->delete();

        return back()->with('success', 'Saved search deleted.');
    }

    public function storeVisit(StoreVisitRequest $request, PropertyListing $property): RedirectResponse
    {
        abort_if($property->status !== 'active', 404);

        if ($property->owner_id === $request->user()->id) {
            return back()->withErrors(['visit' => 'You cannot schedule a visit to your own property.']);
        }

        VisitSchedule::create([
            'property_listing_id' => $property->id,
            'user_id' => $request->user()->id,
            'scheduled_at' => $request->validated('scheduled_at'),
            'note' => $request->validated('note'),
            'status' => 'pending',
        ]);

        return back()->with('success', 'Visit request submitted.');
    }

    public function startConversation(StartConversationRequest $request): RedirectResponse
    {
        $listing = PropertyListing::query()->where('status', 'active')->findOrFail($request->integer('listing_id'));

        if ($listing->owner_id === $request->user()->id) {
            return back()->withErrors(['message' => 'You cannot contact yourself about your own property.']);
        }

        $isBlocked = Block::query()->where(fn ($query) => $query
            ->where(['blocker_id' => $request->user()->id, 'blocked_id' => $listing->owner_id])
            ->orWhere(['blocker_id' => $listing->owner_id, 'blocked_id' => $request->user()->id]))->exists();
        abort_if($isBlocked, 403);

        DB::transaction(function () use ($request, $listing): void {
            $conversation = Conversation::withTrashed()->firstOrCreate([
                'property_listing_id' => $listing->id,
                'starter_id' => $request->user()->id,
                'recipient_id' => $listing->owner_id,
            ], ['last_message_at' => now()]);
            $conversation->restore();
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $request->user()->id,
                'body' => $request->validated('message'),
            ]);
            $conversation->update(['last_message_at' => $message->created_at]);
        });

        return redirect()->route('dashboard.messages')->with('success', 'Conversation started.');
    }

    public function revealContact(Request $request, PropertyListing $property, ContactRevealService $service): RedirectResponse
    {
        abort_unless($property->status === 'active', 404);

        if ($property->owner_id === $request->user()->id) {
            return back()->withErrors(['reveal' => 'You cannot reveal contact for your own listing.']);
        }

        try {
            $result = $service->reveal($request->user(), $property);
        } catch (HttpException $exception) {
            return back()->withErrors(['reveal' => $exception->getMessage()]);
        }

        return back()->with(['revealedPhone' => $result['phone'] ?? '', 'revealRemaining' => $result['remaining']]);
    }

    public function compareIndex(Request $request): View
    {
        $listings = PropertyListing::query()
            ->join('compares', 'compares.property_listing_id', '=', 'property_listings.id')
            ->where('compares.user_id', $request->user()->id)
            ->with(['zone:id,name'])
            ->select('property_listings.*')
            ->orderByDesc('compares.created_at')
            ->get();

        return view('website.dashboard.compare', compact('listings'));
    }

    public function storeCompare(Request $request, PropertyListing $property): RedirectResponse
    {
        abort_unless($property->status === 'active', 404);

        if (Compare::where('user_id', $request->user()->id)->where('property_listing_id', $property->id)->exists()) {
            return back()->with('success', 'Property is already in your compare list.');
        }

        if (Compare::where('user_id', $request->user()->id)->count() >= self::MAX_COMPARE) {
            return back()->withErrors(['compare' => 'You can compare up to '.self::MAX_COMPARE.' properties. Remove one to add another.']);
        }

        Compare::create(['user_id' => $request->user()->id, 'property_listing_id' => $property->id]);

        return back()->with('success', 'Property added to compare.');
    }

    public function destroyCompare(Request $request, PropertyListing $property): RedirectResponse
    {
        Compare::where('user_id', $request->user()->id)->where('property_listing_id', $property->id)->delete();

        return back()->with('success', 'Property removed from compare.');
    }

    public function clearCompare(Request $request): RedirectResponse
    {
        Compare::where('user_id', $request->user()->id)->delete();

        return back()->with('success', 'Compare list cleared.');
    }
}
