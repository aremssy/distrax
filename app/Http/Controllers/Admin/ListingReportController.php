<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyListing;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ListingReportController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status', 'pending')->value();

        $reports = Report::forListings()
            ->with([
                'reportable' => fn ($q) => $q->withTrashed()->select('id', 'title', 'status', 'type', 'owner_id'),
                'reporter:id,name,email',
                'reviewer:id,name',
            ])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $pendingCount = Report::forListings()->pending()->count();

        return view('admin.listings.reports', compact('reports', 'status', 'pendingCount'));
    }

    public function dismiss(Request $request, Report $report): RedirectResponse
    {
        $report->update([
            'status' => 'dismissed',
            'admin_note' => $request->string('admin_note')->value() ?: null,
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Report dismissed.');
    }

    public function warn(Request $request, Report $report): RedirectResponse
    {
        $report->update([
            'status' => 'actioned',
            'admin_note' => $request->string('admin_note')->value() ?: null,
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Report actioned — owner warned.');
    }

    public function removeListing(Report $report): RedirectResponse
    {
        /** @var PropertyListing|null $listing */
        $listing = $report->reportable;

        DB::transaction(function () use ($listing, $report): void {
            if ($listing) {
                $listing->update(['status' => 'archived']);
            }

            $report->update([
                'status' => 'actioned',
                'admin_note' => 'Listing archived by admin via report.',
                'reviewed_by' => auth()->id(),
            ]);

            // Also dismiss all other pending reports on the same listing
            if ($listing) {
                Report::forListings()
                    ->where('reportable_id', $listing->id)
                    ->pending()
                    ->update([
                        'status' => 'actioned',
                        'reviewed_by' => auth()->id(),
                    ]);
            }
        });

        return back()->with('success', 'Listing archived and all reports resolved.');
    }
}
