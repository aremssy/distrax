<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Communication\StoreReportRequest;
use App\Http\Requests\Api\V1\Review\StoreReviewRequest;
use App\Http\Requests\Website\StoreDisputeRequest;
use App\Models\Dispute;
use App\Models\PropertyListing;
use App\Models\Report;
use App\Models\Review;
use App\Models\Technician;
use App\Models\User;
use App\Services\VerifiedReviewGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrustController extends Controller
{
    public function myReports(Request $request): View
    {
        $reports = Report::where('reporter_id', $request->user()->id)->latest()->paginate(perPage: 25, pageName: 'reports')->withQueryString();
        $disputes = Dispute::where('raised_by', $request->user()->id)->with('listing:id,title')->latest()->paginate(perPage: 25, pageName: 'disputes')->withQueryString();

        return view('website.dashboard.reports', compact('reports', 'disputes'));
    }

    public function review(StoreReviewRequest $request, VerifiedReviewGuard $guard): RedirectResponse
    {
        $class = StoreReviewRequest::REVIEWABLE_TYPES[$request->validated('reviewable_type')];
        $target = $class::findOrFail($request->integer('reviewable_id'));
        if (($target instanceof PropertyListing && $target->owner_id === $request->user()->id) || ($target instanceof Technician && $target->user_id === $request->user()->id) || ($target instanceof User && $target->id === $request->user()->id)) {
            return back()->withErrors(['review' => 'You cannot review yourself or your own listing.']);
        }
        if (! $guard->canReview($request->user(), $target)) {
            return back()->withErrors(['review' => 'Reviews are available only after a completed booking or job.']);
        }
        Review::updateOrCreate(['reviewer_id' => $request->user()->id, 'reviewable_type' => $target->getMorphClass(), 'reviewable_id' => $target->id], ['rating' => $request->integer('rating'), 'body' => $request->validated('body'), 'is_verified' => true, 'is_visible' => true]);

        return back()->with('success', 'Verified review submitted.');
    }

    public function report(StoreReportRequest $request): RedirectResponse
    {
        $class = StoreReportRequest::REPORTABLE_TYPES[$request->validated('reportable_type')];
        if ($class === User::class && $request->integer('reportable_id') === $request->user()->id) {
            return back()->withErrors(['report' => 'You cannot report yourself.']);
        }
        $model = new $class;
        Report::updateOrCreate(['reporter_id' => $request->user()->id, 'reportable_type' => $model->getMorphClass(), 'reportable_id' => $request->integer('reportable_id')], ['reason' => $request->validated('reason'), 'details' => $request->validated('details'), 'status' => 'pending']);

        return back()->with('success', 'Report submitted for review.');
    }

    public function dispute(StoreDisputeRequest $request): RedirectResponse
    {
        $listing = PropertyListing::findOrFail($request->integer('property_listing_id'));
        if ($listing->owner_id === $request->user()->id) {
            return back()->withErrors(['dispute' => 'You cannot raise a dispute against yourself.']);
        }
        Dispute::create(['raised_by' => $request->user()->id, 'against_user_id' => $listing->owner_id, 'property_listing_id' => $listing->id, 'subject' => $request->validated('subject'), 'description' => $request->validated('description'), 'status' => 'open']);

        return back()->with('success', 'Dispute ticket opened.');
    }
}
