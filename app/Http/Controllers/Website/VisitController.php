<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Visit\UpdateVisitRequest;
use App\Models\VisitSchedule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function index(Request $request): View
    {
        $requestedVisits = VisitSchedule::query()->with(['listing:id,slug,title,owner_id,status', 'listing.owner:id,name'])->where('user_id', $request->user()->id)->latest('scheduled_at')->paginate(perPage: 25, pageName: 'requested')->withQueryString();
        $incomingVisits = VisitSchedule::query()->with(['listing:id,slug,title,owner_id,status', 'user:id,name'])->whereHas('listing', fn ($query) => $query->where('owner_id', $request->user()->id))->latest('scheduled_at')->paginate(perPage: 25, pageName: 'incoming')->withQueryString();

        return view('website.dashboard.activity', compact('requestedVisits', 'incomingVisits'));
    }

    public function update(UpdateVisitRequest $request, VisitSchedule $visit): RedirectResponse
    {
        $isOwner = $visit->listing->owner_id === $request->user()->id;
        $isRequester = $visit->user_id === $request->user()->id;
        abort_unless($isOwner || $isRequester, 403);

        if ($visit->status !== 'pending') {
            return back()->withErrors(['visit' => 'Only pending visits can be updated.']);
        }

        if ($isRequester && ! $isOwner && $request->validated('status') !== 'cancelled') {
            abort(403);
        }

        $visit->update(['status' => $request->validated('status'), 'note' => $request->validated('note', $visit->note)]);

        return back()->with('success', 'Visit request updated.');
    }
}
