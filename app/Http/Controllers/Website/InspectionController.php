<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\PropertyListing;
use App\Services\InspectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InspectionController extends Controller
{
    public function __construct(private InspectionService $inspections)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $inspections = Inspection::query()
            ->with(['listing', 'inspector'])
            ->where(fn ($q) => $q->where('booked_by', $user->id)
                ->orWhere('inspector_id', $user->id)
                ->orWhereHas('listing', fn ($l) => $l->where('owner_id', $user->id)))
            ->orderByDesc('scheduled_at')
            ->paginate(15);

        return view('website.pages.inspections.index', compact('inspections'));
    }

    public function create(PropertyListing $property): View
    {
        return view('website.pages.inspections.create', ['listing' => $property]);
    }

    public function store(Request $request, PropertyListing $property): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:physical,virtual'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        try {
            $inspection = $this->inspections->book($property, $request->user(), $data);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inspections.show', $inspection)
            ->with('success', __('Inspection requested. You will be notified once an inspector is assigned.'));
    }

    public function show(Inspection $inspection): View
    {
        abort_unless($this->participant($inspection), 403);

        return view('website.pages.inspections.show', [
            'inspection' => $inspection->load(['listing.owner', 'inspector', 'evidence', 'buyer']),
        ]);
    }

    public function acknowledge(Request $request, Inspection $inspection): RedirectResponse
    {
        try {
            $this->inspections->acknowledge($inspection, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inspections.show', $inspection)
            ->with('success', __('Report acknowledged. The inspection condition on your offer is now satisfied.'));
    }

    public function cancel(Request $request, Inspection $inspection): RedirectResponse
    {
        try {
            $this->inspections->cancel($inspection, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inspections.show', $inspection)
            ->with('success', __('Inspection cancelled.'));
    }

    private function participant(Inspection $inspection): bool
    {
        $user = auth()->user();

        return $inspection->booked_by === $user->id
            || $inspection->listing->owner_id === $user->id
            || ($inspection->inspector_id && $inspection->inspector_id === $user->id);
    }
}
