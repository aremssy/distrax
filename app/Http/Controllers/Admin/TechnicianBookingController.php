<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuoteTechnicianBookingRequest;
use App\Http\Requests\Admin\UpdateTechnicianBookingRequest;
use App\Models\TechnicianBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TechnicianBookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = TechnicianBooking::with(['technician.user:id,name,email', 'technician.category:id,name,commission_rate', 'user:id,name,email'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->boolean('urgent'), fn ($query) => $query->where('is_urgent', true))
            ->latest()
            ->paginate(25);

        return view('admin.tech-bookings.index', compact('bookings'));
    }

    public function show(TechnicianBooking $booking): View
    {
        return view('admin.tech-bookings.show', [
            'booking' => $booking->load(['technician.user', 'technician.category', 'user', 'quotes.technician.user', 'payments']),
        ]);
    }

    public function update(UpdateTechnicianBookingRequest $request, TechnicianBooking $booking): RedirectResponse
    {
        $data = $request->validated();

        $data['is_urgent'] = $request->boolean('is_urgent', $booking->is_urgent);
        $booking->update($data);

        return redirect()->route('admin.tech-bookings.show', $booking)->with('success', 'Booking updated.');
    }

    public function quote(QuoteTechnicianBookingRequest $request, TechnicianBooking $booking): RedirectResponse
    {
        DB::transaction(function () use ($booking, $request): void {
            $booking->quotes()->create($request->validated());
            $booking->update(['status' => 'quoted']);
        });

        return redirect()->route('admin.tech-bookings.show', $booking)->with('success', 'Quote added.');
    }
}
