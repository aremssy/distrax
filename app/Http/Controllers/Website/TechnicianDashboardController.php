<?php

namespace App\Http\Controllers\Website;

use App\Events\TechnicianBookingStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Website\StoreTechnicianQuoteRequest;
use App\Http\Requests\Website\UpdateTechnicianProfileRequest;
use App\Models\MaintenanceRequest;
use App\Models\Quote;
use App\Models\Technician;
use App\Models\TechnicianBooking;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The technician's own dashboard: their profile, and the jobs booked with them.
 *
 * Every route here is behind the `technician` middleware, which resolves the
 * profile and stashes it on the request, so it is always present.
 */
class TechnicianDashboardController extends Controller
{
    /**
     * Show the technician's editable profile.
     */
    public function editProfile(Request $request): View
    {
        $technician = $this->technician($request);
        $technician->load(['category:id,name', 'zone:id,name']);

        return view('website.technician.profile', [
            'technician' => $technician,
            'zones' => $this->zones(),
            'stats' => $this->stats($technician),
        ]);
    }

    /**
     * Update the profile. Status and category stay admin-controlled.
     */
    public function updateProfile(UpdateTechnicianProfileRequest $request): RedirectResponse
    {
        $technician = $this->technician($request);

        $technician->update([
            ...$request->safe()->only(['zone_id', 'bio', 'experience_years', 'hourly_rate']),
            'skills' => $request->safe()->array('skills'),
            'is_available' => $request->boolean('is_available'),
        ]);

        return redirect()->route('technician.profile.edit')->with('success', 'Profile updated.');
    }

    /**
     * List the jobs booked with this technician.
     */
    public function bookings(Request $request): View
    {
        $technician = $this->technician($request);

        $statuses = ['pending', 'quoted', 'accepted', 'in_progress', 'completed', 'cancelled'];
        $statusFilter = $request->string('status')->value();

        if (! in_array($statusFilter, $statuses, true)) {
            $statusFilter = null;
        }

        $bookings = TechnicianBooking::query()
            ->where('technician_id', $technician->id)
            ->with(['user:id,name,phone'])
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $statusCounts = TechnicianBooking::query()
            ->where('technician_id', $technician->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('website.technician.bookings.index', compact('bookings', 'statuses', 'statusFilter', 'statusCounts'));
    }

    /**
     * List maintenance requests assigned to this technician. Status changes are
     * submitted to the shared dashboard.maintenance.update route — the same
     * MaintenanceRequestService transition rules apply regardless of which
     * dashboard the request came from.
     */
    public function maintenanceRequests(Request $request): View
    {
        $technician = $this->technician($request);

        $requests = MaintenanceRequest::with(['listing:id,title,slug', 'tenant:id,name', 'owner:id,name'])
            ->where('technician_id', $technician->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('website.technician.maintenance-requests', compact('requests'));
    }

    /**
     * Show one job with its quote history.
     */
    public function showBooking(Request $request, TechnicianBooking $technicianBooking): View
    {
        $this->authorizeBooking($request, $technicianBooking);

        $technicianBooking->load(['user:id,name,email,phone', 'quotes' => fn ($query) => $query->latest()]);

        return view('website.technician.bookings.show', ['booking' => $technicianBooking]);
    }

    /**
     * Submit a quote for a pending job. Mirrors the API flow so both surfaces
     * move the booking to 'quoted' and fire the same notification event.
     */
    public function storeQuote(StoreTechnicianQuoteRequest $request, TechnicianBooking $technicianBooking): RedirectResponse
    {
        $technician = $this->authorizeBooking($request, $technicianBooking);

        if ($technicianBooking->status !== 'pending') {
            return back()->withErrors(['amount' => 'A quote can only be submitted for pending jobs.'])->withInput();
        }

        Quote::create([
            'technician_booking_id' => $technicianBooking->id,
            'technician_id' => $technician->id,
            'amount' => $request->integer('amount'),
            'description' => $request->input('description'),
            'valid_until' => $request->input('valid_until'),
            'status' => 'pending',
        ]);

        $technicianBooking->update(['status' => 'quoted']);

        TechnicianBookingStatusUpdated::dispatch($technicianBooking->fresh(['technician.user', 'user']), 'pending');

        return redirect()->route('technician.bookings.show', $technicianBooking)->with('success', 'Quote sent to the customer.');
    }

    /**
     * Move an accepted job forward, or decline it.
     *
     * Only the transitions a technician owns are allowed; the customer drives
     * everything up to 'accepted'.
     */
    public function updateBookingStatus(Request $request, TechnicianBooking $technicianBooking): RedirectResponse
    {
        $this->authorizeBooking($request, $technicianBooking);

        $allowed = [
            'pending' => ['cancelled'],
            'quoted' => ['cancelled'],
            'accepted' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
        ];

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:in_progress,completed,cancelled'],
        ]);

        $from = $technicianBooking->status;

        if (! in_array($validated['status'], $allowed[$from] ?? [], true)) {
            return back()->withErrors(['status' => "A job that is {$from} cannot be moved to {$validated['status']}."]);
        }

        $technicianBooking->update(['status' => $validated['status']]);

        TechnicianBookingStatusUpdated::dispatch($technicianBooking->fresh(['technician.user', 'user']), $from);

        return back()->with('success', 'Job updated.');
    }

    /**
     * Reject the booking unless it belongs to the signed-in technician.
     */
    private function authorizeBooking(Request $request, TechnicianBooking $booking): Technician
    {
        $technician = $this->technician($request);

        abort_unless($booking->technician_id === $technician->id, 404);

        return $technician;
    }

    /**
     * The profile resolved by the `technician` middleware.
     */
    private function technician(Request $request): Technician
    {
        return $request->attributes->get('technician');
    }

    /**
     * @return array<string, int|float>
     */
    private function stats(Technician $technician): array
    {
        $bookings = TechnicianBooking::query()->where('technician_id', $technician->id);

        return [
            'total_jobs' => (clone $bookings)->count(),
            'completed_jobs' => (clone $bookings)->where('status', 'completed')->count(),
            'open_jobs' => (clone $bookings)->whereIn('status', ['pending', 'quoted', 'accepted', 'in_progress'])->count(),
            'rating' => round((float) $technician->reviews()->avg('rating'), 2),
        ];
    }

    /**
     * @return Collection<int, Zone>
     */
    private function zones(): Collection
    {
        return Zone::query()->active()->whereIn('type', ['city', 'area'])->orderBy('name')->get(['id', 'name', 'type']);
    }
}
