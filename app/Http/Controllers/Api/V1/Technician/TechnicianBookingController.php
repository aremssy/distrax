<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Events\TechnicianBookingStatusUpdated;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Technician\StoreQuoteRequest;
use App\Http\Requests\Api\V1\Technician\StoreTechnicianBookingRequest;
use App\Http\Resources\Api\V1\QuoteResource;
use App\Http\Resources\Api\V1\TechnicianBookingResource;
use App\Models\Quote;
use App\Models\Technician;
use App\Models\TechnicianBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TechnicianBookingController extends ApiController
{
    /**
     * POST /api/v1/technician-bookings
     *
     * Book a technician. Creates booking in 'pending' status awaiting a quote.
     */
    public function store(StoreTechnicianBookingRequest $request): JsonResponse
    {
        $user = $request->user();
        $technician = Technician::find($request->integer('technician_id'));

        if ($technician->user_id === $user->id) {
            return $this->error('You cannot book yourself.', 422);
        }

        $booking = TechnicianBooking::create([
            'technician_id' => $technician->id,
            'user_id' => $user->id,
            'description' => $request->input('description'),
            'scheduled_at' => $request->input('scheduled_at'),
            'address' => $request->input('address'),
            'is_urgent' => $request->boolean('is_urgent'),
            'status' => 'pending',
        ]);

        $booking->load(['technician.user', 'technician.category', 'user', 'quotes']);

        TechnicianBookingStatusUpdated::dispatch($booking, 'new');

        return $this->created(new TechnicianBookingResource($booking), 'Booking created. Awaiting a quote from the technician.');
    }

    /**
     * GET /api/v1/technician-bookings
     *
     * List bookings. role=customer (default) shows bookings I placed.
     * role=technician shows bookings assigned to my technician profile.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'role' => ['sometimes', Rule::in(['customer', 'technician'])],
            'status' => ['sometimes', Rule::in(['pending', 'quoted', 'accepted', 'in_progress', 'completed', 'cancelled'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();
        $role = $request->input('role', 'customer');
        $perPage = min((int) $request->input('per_page', 15), 50);

        $query = TechnicianBooking::with(['technician.user', 'technician.category', 'user', 'quotes'])
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s));

        if ($role === 'technician') {
            $technician = Technician::where('user_id', $user->id)->first();

            if (! $technician) {
                return $this->error('You do not have a technician profile.', 404);
            }

            $query->where('technician_id', $technician->id);
        } else {
            $query->where('user_id', $user->id);
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->paginated($paginator, TechnicianBookingResource::class);
    }

    /**
     * GET /api/v1/technician-bookings/{booking}
     *
     * Show a single booking. Only the customer or the assigned technician may view.
     */
    public function show(Request $request, TechnicianBooking $technicianBooking): JsonResponse
    {
        $this->authorize('view', $technicianBooking);

        $technicianBooking->load(['technician.user', 'technician.category', 'user', 'quotes.technician.user', 'payments']);

        return $this->success(new TechnicianBookingResource($technicianBooking));
    }

    /**
     * POST /api/v1/technician-bookings/{booking}/quote
     *
     * Technician submits a quote. Booking must be 'pending'.
     * Moves booking status to 'quoted'.
     */
    public function submitQuote(StoreQuoteRequest $request, TechnicianBooking $technicianBooking): JsonResponse
    {
        $user = $request->user();
        $technician = Technician::where('user_id', $user->id)->first();

        if (! $technician || $technicianBooking->technician_id !== $technician->id) {
            return $this->error('You are not the technician for this booking.', 403);
        }

        if ($technicianBooking->status !== 'pending') {
            return $this->error('A quote can only be submitted for pending bookings.', 422);
        }

        $quote = Quote::create([
            'technician_booking_id' => $technicianBooking->id,
            'technician_id' => $technician->id,
            'amount' => $request->integer('amount'),
            'description' => $request->input('description'),
            'valid_until' => $request->input('valid_until'),
            'status' => 'pending',
        ]);

        $technicianBooking->update(['status' => 'quoted']);

        TechnicianBookingStatusUpdated::dispatch($technicianBooking->fresh(['technician.user', 'user']), 'pending');

        $quote->load('technician.user');

        return $this->created(new QuoteResource($quote), 'Quote submitted successfully.');
    }

    /**
     * POST /api/v1/technician-bookings/{booking}/quote/accept
     *
     * Customer accepts the latest pending quote.
     * Booking moves to 'accepted', agreed_amount set, other quotes rejected.
     */
    public function acceptQuote(Request $request, TechnicianBooking $technicianBooking): JsonResponse
    {
        $this->authorize('respondToQuote', $technicianBooking);

        if ($technicianBooking->status !== 'quoted') {
            return $this->error('No pending quote to accept.', 422);
        }

        $quote = $technicianBooking->quotes()
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (! $quote) {
            return $this->error('No pending quote found.', 422);
        }

        // Reject all other pending quotes
        $technicianBooking->quotes()
            ->where('status', 'pending')
            ->where('id', '!=', $quote->id)
            ->update(['status' => 'rejected']);

        $quote->update(['status' => 'accepted']);

        $technicianBooking->update([
            'status' => 'accepted',
            'agreed_amount' => $quote->amount,
        ]);

        TechnicianBookingStatusUpdated::dispatch($technicianBooking->fresh(['technician.user', 'user']), 'quoted');

        $technicianBooking->load(['technician.user', 'technician.category', 'user', 'quotes.technician.user']);

        return $this->success(new TechnicianBookingResource($technicianBooking), 'Quote accepted. The technician will confirm.');
    }

    /**
     * POST /api/v1/technician-bookings/{booking}/quote/reject
     *
     * Customer rejects the latest pending quote.
     * Booking reverts to 'pending' if no other pending quotes remain.
     */
    public function rejectQuote(Request $request, TechnicianBooking $technicianBooking): JsonResponse
    {
        $this->authorize('respondToQuote', $technicianBooking);

        if ($technicianBooking->status !== 'quoted') {
            return $this->error('No pending quote to reject.', 422);
        }

        $quote = $technicianBooking->quotes()
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (! $quote) {
            return $this->error('No pending quote found.', 422);
        }

        $quote->update(['status' => 'rejected']);

        // Revert to pending if no other pending quotes remain
        $remainingPending = $technicianBooking->quotes()->where('status', 'pending')->count();

        if ($remainingPending === 0) {
            $technicianBooking->update(['status' => 'pending']);
        }

        TechnicianBookingStatusUpdated::dispatch($technicianBooking->fresh(['technician.user', 'user']), 'quoted');

        $technicianBooking->load(['technician.user', 'technician.category', 'user', 'quotes.technician.user']);

        return $this->success(new TechnicianBookingResource($technicianBooking), 'Quote rejected.');
    }
}
