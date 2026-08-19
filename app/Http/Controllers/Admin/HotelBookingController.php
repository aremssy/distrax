<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FetchHotelAvailabilityRequest;
use App\Http\Requests\Admin\RefundHotelBookingRequest;
use App\Http\Requests\Admin\UpdateHotelBookingRequest;
use App\Models\AvailabilityCalendar;
use App\Models\HotelBooking;
use App\Models\PropertyListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HotelBookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = HotelBooking::with(['listing:id,title', 'user:id,name,email'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(25);

        return view('admin.hotel-bookings.index', compact('bookings'));
    }

    public function show(HotelBooking $booking): View
    {
        return view('admin.hotel-bookings.show', [
            'booking' => $booking->load(['listing', 'user', 'payments']),
        ]);
    }

    public function update(UpdateHotelBookingRequest $request, HotelBooking $booking): RedirectResponse
    {
        $data = $request->validated();

        if ($data['status'] === 'cancelled' && ! $booking->cancelled_at) {
            $data['cancelled_at'] = now();
        }

        $booking->update($data);

        return redirect()->route('admin.hotel-bookings.show', $booking)->with('success', 'Booking updated.');
    }

    public function availability(FetchHotelAvailabilityRequest $request, int $listingId): JsonResponse
    {
        $data = $request->validated();

        abort_unless(PropertyListing::whereKey($listingId)->exists(), 404);

        $calendar = AvailabilityCalendar::where('property_listing_id', $listingId)
            ->when($data['from'] ?? null, fn ($query, string $from) => $query->whereDate('date', '>=', $from))
            ->when($data['to'] ?? null, fn ($query, string $to) => $query->whereDate('date', '<=', $to))
            ->orderBy('date')
            ->paginate(60);

        return response()->json(['availability' => $calendar]);
    }

    public function refund(RefundHotelBookingRequest $request, HotelBooking $booking): RedirectResponse
    {
        $data = $request->validated();

        // Serialize concurrent refunds on the same booking (as wallet/payout do) so two
        // partial refunds can't both read a stale refunded_amount and race the total.
        $alreadyRefunded = DB::transaction(function () use ($booking, $data): bool {
            $locked = HotelBooking::whereKey($booking->getKey())->lockForUpdate()->first();

            if ($locked->status === 'refunded' || $locked->refunded_amount >= $locked->amount) {
                return true;
            }

            $refundedTotal = min($locked->amount, $locked->refunded_amount + $data['amount']);

            $locked->update([
                'status' => $refundedTotal >= $locked->amount ? 'refunded' : 'partially_refunded',
                'refunded_amount' => $refundedTotal,
            ]);

            return false;
        });

        if ($alreadyRefunded) {
            return back()->with('error', 'This booking has already been fully refunded.');
        }

        return redirect()->route('admin.hotel-bookings.show', $booking)->with('success', 'Refund processed.');
    }
}
