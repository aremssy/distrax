<?php

namespace App\Http\Controllers\Api\V1\Booking;

use App\Events\BookingStatusUpdated;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Booking\StoreBookingRequest;
use App\Http\Resources\Api\V1\BookingResource;
use App\Models\HotelBooking;
use App\Models\PropertyListing;
use App\Services\AvailabilityService;
use App\Services\CancellationService;
use App\Services\IdempotencyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class BookingController extends ApiController
{
    public function __construct(private IdempotencyService $idempotency) {}

    /**
     * POST /api/v1/bookings
     *
     * Create a hotel booking.
     *
     * Idempotency: include X-Idempotency-Key header to make the request
     * idempotent. Repeating with the same key durably returns the original
     * booking response, even after a cache flush.
     *
     * Race conditions: a distributed lock on (listing, check_in, check_out)
     * prevents double-booking under concurrent requests.
     */
    public function store(StoreBookingRequest $request, AvailabilityService $availability): JsonResponse
    {
        $user = $request->user();
        $idempotencyKey = $request->header('X-Idempotency-Key');

        if (! $idempotencyKey) {
            return $this->createBooking($request, $availability);
        }

        $outcome = $this->idempotency->remember(
            $user,
            'booking:store',
            $idempotencyKey,
            fn () => $this->buildBookingOutcome($request, $availability)
        );

        return response()->json($outcome['body'], $outcome['status']);
    }

    private function createBooking(Request $request, AvailabilityService $availability): JsonResponse
    {
        $user = $request->user();
        $listing = PropertyListing::findOrFail($request->integer('listing_id'));

        if ($listing->type !== 'hotel') {
            return $this->error('Bookings are only available for hotel-type listings.', 422);
        }

        if ($listing->status !== 'active') {
            return $this->error('This listing is not available for booking.', 422);
        }

        if ($listing->owner_id === $user->id) {
            return $this->error('You cannot book your own listing.', 422);
        }

        $checkIn = Carbon::parse($request->input('check_in'))->startOfDay();
        $checkOut = Carbon::parse($request->input('check_out'))->startOfDay();
        $nights = (int) $checkIn->diffInDays($checkOut);
        $minNights = (int) setting('hotel_min_stay_nights', 1);

        if ($nights < $minNights) {
            return $this->error("Minimum stay is {$minNights} night(s).", 422);
        }

        // ── Distributed lock to prevent race conditions ───────────────────────
        $lockKey = "booking:lock:{$listing->id}:{$checkIn->toDateString()}:{$checkOut->toDateString()}";
        $lock = Cache::lock($lockKey, 30);

        if (! $lock->block(5)) {
            return $this->error('Another booking is being processed for these dates. Please try again.', 409);
        }

        try {
            // ── Availability check (inside lock) ──────────────────────────────
            if (! $availability->isRangeAvailable($listing, $checkIn, $checkOut)) {
                return $this->error('One or more nights in the selected range are unavailable.', 409);
            }

            $amount = $availability->computeAmount($listing, $checkIn, $checkOut);

            $booking = HotelBooking::create([
                'property_listing_id' => $listing->id,
                'user_id' => $user->id,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'guests' => $request->integer('guests', 1),
                'amount' => $amount,
                'status' => 'pending',
                'special_requests' => $request->input('special_requests'),
            ]);
        } finally {
            $lock->release();
        }

        $booking->load(['listing', 'user', 'payments']);

        BookingStatusUpdated::dispatch($booking, 'new');

        return $this->created(new BookingResource($booking), 'Booking created. Complete payment to confirm.');
    }

    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    private function buildBookingOutcome(Request $request, AvailabilityService $availability): array
    {
        $response = $this->createBooking($request, $availability);

        return ['status' => $response->getStatusCode(), 'body' => $response->getData(true)];
    }

    /**
     * GET /api/v1/bookings
     *
     * List the authenticated user's bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', Rule::in(['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'refunded'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = min((int) $request->input('per_page', 15), 50);

        $paginator = HotelBooking::where('user_id', $request->user()->id)
            ->with(['listing', 'payments'])
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('check_in')
            ->paginate($perPage);

        return $this->paginated($paginator, BookingResource::class);
    }

    /**
     * GET /api/v1/bookings/{booking}
     *
     * Show a single booking. Guest or listing owner may view.
     */
    public function show(Request $request, HotelBooking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $booking->load(['listing', 'user', 'payments']);

        return $this->success(new BookingResource($booking));
    }

    /**
     * POST /api/v1/bookings/{booking}/cancel
     *
     * Cancel a booking and compute the refund entitlement.
     * The actual refund payment is processed in Phase 10.
     *
     * Query param: ?preview=1 returns refund info without cancelling.
     */
    public function cancel(Request $request, HotelBooking $booking, CancellationService $cancellation): JsonResponse
    {
        $this->authorize('cancel', $booking);

        if (! in_array($booking->status, ['pending', 'confirmed'], true)) {
            return $this->error('Only pending or confirmed bookings can be cancelled.', 422);
        }

        $refundInfo = $cancellation->compute($booking);
        $previousStatus = $booking->status;

        // Preview mode: return policy without mutating
        if ($request->boolean('preview')) {
            return $this->success([
                'booking_id' => $booking->id,
                'current_status' => $booking->status,
                'amount' => (int) $booking->amount,
                'currency' => setting('default_currency', 'BDT'),
                'cancellation_policy' => $refundInfo,
            ], 'Cancellation preview.');
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'refunded_amount' => $refundInfo['refundable_amount'],
        ]);

        $booking->load(['listing', 'payments']);
        $booking->refund_info = $refundInfo;

        BookingStatusUpdated::dispatch($booking, $previousStatus);

        return $this->success(
            new BookingResource($booking),
            $refundInfo['is_refundable']
                ? 'Booking cancelled. Refund of '.number_format($refundInfo['refundable_amount']).' will be processed.'
                : 'Booking cancelled. No refund applies under the current policy.'
        );
    }
}
