<?php

namespace App\Http\Controllers\Website;

use App\Events\BookingStatusUpdated;
use App\Events\TechnicianBookingStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Technician\StoreTechnicianBookingRequest;
use App\Http\Requests\Website\StoreHotelBookingRequest;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\HotelBooking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PropertyListing;
use App\Models\Technician;
use App\Models\TechnicianBooking;
use App\Models\TechnicianCategory;
use App\Models\Zone;
use App\Services\AvailabilityService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketplaceController extends Controller
{
    public function technicians(Request $request): View
    {
        $technicians = Technician::query()->with(['user:id,name,avatar', 'category:id,name,slug', 'zone:id,name'])->where('status', 'active')->where('is_verified', true)->where('is_available', true)
            ->when($request->integer('category_id'), fn (Builder $query, int $id) => $query->where('technician_category_id', $id))
            ->when($request->integer('zone_id'), fn (Builder $query, int $id) => $query->where('zone_id', $id))
            ->orderByDesc('rating')->paginate(12)->withQueryString();
        $hasFilters = $request->integer('category_id') > 0 || $request->integer('zone_id') > 0;
        // Live search swaps only the results grid, so skip the full layout.
        if ($request->ajax()) {
            return view('website.marketplace.technicians._results', compact('technicians', 'hasFilters'));
        }

        $categories = TechnicianCategory::where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);
        $zones = Zone::active()->whereIn('type', ['city', 'area'])->orderBy('name')->get(['id', 'name']);

        return view('website.marketplace.technicians.index', compact('technicians', 'categories', 'zones', 'hasFilters'));
    }

    public function technician(string $technician): View|RedirectResponse
    {
        if (ctype_digit($technician) && $slug = Technician::whereKey($technician)->value('slug')) {
            return redirect()->route('technicians.show', $slug, 301);
        }

        $technician = Technician::where('slug', $technician)->firstOrFail();
        abort_unless($technician->status === 'active' && $technician->is_verified, 404);
        $technician->load(['user:id,name,avatar,verification_status', 'category:id,name', 'zone:id,name']);

        return view('website.marketplace.technicians.show', compact('technician'));
    }

    public function agencies(Request $request): View
    {
        $search = trim((string) $request->string('q'));
        $verifiedOnly = $request->boolean('verified');

        $agencies = Agency::query()
            ->with('zone:id,name')
            ->withCount([
                'agents',
                'listings' => fn (Builder $query) => $query->where('status', 'active'),
                'reviews' => fn (Builder $query) => $query->where('is_visible', true),
            ])
            // The stored `rating` column is never recalculated, so the public rating is
            // averaged from the visible reviews instead.
            ->withAvg(['reviews as reviews_avg_rating' => fn (Builder $query) => $query->where('is_visible', true)], 'rating')
            ->where('status', 'active')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhereHas('zone', fn (Builder $zone) => $zone->where('name', 'like', "%{$search}%"));
            }))
            ->when($verifiedOnly, fn (Builder $query) => $query->where('is_verified', true))
            ->orderByDesc('is_verified')
            ->orderByDesc('listings_count')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        // Live search swaps only the results grid, so skip the full layout.
        if ($request->ajax()) {
            return view('website.marketplace.agencies._results', compact('agencies', 'search', 'verifiedOnly'));
        }

        return view('website.marketplace.agencies.index', compact('agencies', 'search', 'verifiedOnly'));
    }

    public function agency(Agency $agency): View
    {
        abort_unless($agency->status === 'active', 404);

        $agency->load([
            'zone:id,name',
            'agents' => fn ($query) => $query->where('is_active', true)
                ->with('user:id,name,avatar')
                ->withCount(['listings' => fn (Builder $listing) => $listing->where('status', 'active')]),
            'listings' => fn ($query) => $query->where('status', 'active')
                ->with(['zone:id,name', 'owner:id,name,avatar', 'coverImage:id,property_listing_id,path,disk,is_cover,sort_order'])
                ->withViewerFavorite()
                ->latest()
                ->limit(12),
            'reviews' => fn ($query) => $query->where('is_visible', true)
                ->with('reviewer:id,name,avatar')
                ->latest()
                ->limit(10),
        ]);

        $agency->loadCount([
            'listings' => fn (Builder $query) => $query->where('status', 'active'),
            'agents' => fn (Builder $query) => $query->where('is_active', true),
            'reviews' => fn (Builder $query) => $query->where('is_visible', true),
        ]);

        $reviewsAverage = (float) $agency->reviews()->where('is_visible', true)->avg('rating');

        return view('website.marketplace.agencies.show', compact('agency', 'reviewsAverage'));
    }

    public function agents(Request $request): View
    {
        $search = trim((string) $request->string('q'));

        $agents = Agent::query()
            ->with(['user:id,name,avatar,verification_status', 'agency:id,name,slug'])
            ->where('is_active', true)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('agency', fn (Builder $agency) => $agency->where('name', 'like', "%{$search}%"));
            }))
            ->withCount(['listings' => fn (Builder $query) => $query->where('status', 'active')])
            ->orderByDesc('listings_count')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        // Live search swaps only the results grid, so skip the full layout.
        if ($request->ajax()) {
            return view('website.marketplace.agents._results', compact('agents', 'search'));
        }

        return view('website.marketplace.agents.index', compact('agents', 'search'));
    }

    public function agent(string $agent): View|RedirectResponse
    {
        if (ctype_digit($agent) && $slug = Agent::whereKey($agent)->value('slug')) {
            return redirect()->route('agents.show', $slug, 301);
        }

        $agent = Agent::where('slug', $agent)->firstOrFail();
        abort_unless($agent->is_active, 404);
        $agent->load(['user:id,name,avatar', 'agency:id,name,slug,logo']);
        $listings = $agent->listings()->where('status', 'active')->with(['zone:id,name', 'owner:id,name,avatar', 'coverImage:id,property_listing_id,path,disk,is_cover,sort_order'])->withViewerFavorite()->latest()->limit(12)->get();

        return view('website.marketplace.agents.show', compact('agent', 'listings'));
    }

    public function bookHotel(StoreHotelBookingRequest $request, AvailabilityService $availability, WalletService $wallets): RedirectResponse
    {
        $listing = PropertyListing::query()->where('type', 'hotel')->where('status', 'active')->findOrFail($request->integer('listing_id'));
        if ($listing->owner_id === $request->user()->id) {
            return back()->withErrors(['booking' => 'You cannot book your own property.']);
        }
        $checkIn = Carbon::parse($request->validated('check_in'));
        $checkOut = Carbon::parse($request->validated('check_out'));
        $isWallet = $request->validated('payment_method') === 'wallet';
        $gateway = $isWallet ? 'wallet' : (string) $request->validated('gateway');

        try {
            [$booking, $payment] = DB::transaction(function () use ($request, $listing, $availability, $checkIn, $checkOut, $isWallet, $gateway, $wallets): array {
                PropertyListing::lockForUpdate()->findOrFail($listing->id);
                if (! $availability->isRangeAvailable($listing, $checkIn, $checkOut)) {
                    throw new \RuntimeException('Those dates are no longer available.');
                }

                $booking = HotelBooking::create(['property_listing_id' => $listing->id, 'user_id' => $request->user()->id, 'check_in' => $checkIn, 'check_out' => $checkOut, 'guests' => $request->integer('guests', 1), 'amount' => $availability->computeAmount($listing, $checkIn, $checkOut), 'status' => 'pending', 'special_requests' => $request->validated('special_requests')]);

                $payment = Payment::create([
                    'user_id' => $request->user()->id,
                    'payable_type' => $booking->getMorphClass(),
                    'payable_id' => $booking->id,
                    'gateway' => $gateway,
                    'amount' => $booking->amount,
                    'currency' => $booking->currency_code ?? setting('default_currency', 'BDT'),
                    'status' => 'pending',
                ]);

                if ($isWallet) {
                    $wallets->debit($wallets->findOrCreateForUser($request->user()), $booking->amount, 'Hotel booking #'.$booking->id, $payment);
                    $payment->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
                    $booking->update(['status' => 'confirmed']);

                    Invoice::create([
                        'invoice_number' => 'INV-'.strtoupper(Str::random(12)),
                        'user_id' => $request->user()->id,
                        'invoiceable_type' => $booking->getMorphClass(),
                        'invoiceable_id' => $booking->id,
                        'type' => 'hotel_booking',
                        'amount' => $booking->amount,
                        'currency' => $booking->currency_code ?? setting('default_currency', 'BDT'),
                        'status' => 'paid',
                        'issued_at' => now(),
                        'paid_at' => now(),
                    ]);
                }

                return [$booking, $payment];
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['booking' => $exception->getMessage()]);
        }

        BookingStatusUpdated::dispatch($booking, 'new');

        if ($isWallet) {
            return back()->with('success', 'Booking confirmed and paid from your wallet.');
        }

        return redirect()->route('checkout.start', $payment);
    }

    public function bookTechnician(StoreTechnicianBookingRequest $request): RedirectResponse
    {
        $technician = Technician::findOrFail($request->integer('technician_id'));
        if ($technician->user_id === $request->user()->id) {
            return back()->withErrors(['booking' => 'You cannot book yourself.']);
        }
        $booking = TechnicianBooking::create(['technician_id' => $technician->id, 'user_id' => $request->user()->id, 'description' => $request->validated('description'), 'scheduled_at' => $request->validated('scheduled_at'), 'address' => $request->validated('address'), 'is_urgent' => $request->boolean('is_urgent'), 'status' => 'pending']);
        TechnicianBookingStatusUpdated::dispatch($booking, 'new');

        return back()->with('success', 'Technician booking created. Awaiting a quote.');
    }
}
