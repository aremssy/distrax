<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\InstitutionalAccount;
use App\Models\PropertyListing;
use App\Services\InstitutionalCsvImporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InstitutionalController extends Controller
{
    public function index(Request $request): View
    {
        $account = $request->user()->institutionalAccount;

        $listings = collect();
        $batches = collect();

        if ($account) {
            $listings = PropertyListing::with(['zone:id,name', 'coverImage'])
                ->where('owner_id', $account->user_id)
                ->orderByDesc('updated_at')
                ->get();

            $batches = $account->batches()->with('uploader:id,name')->latest()->get();
        }

        return view('website.dashboard.institutional', [
            'account' => $account,
            'listings' => $listings,
            'batches' => $batches,
            'pendingCount' => $listings->where('status', 'pending')->count(),
            'activeCount' => $listings->where('status', 'active')->count(),
        ]);
    }

    public function store(Request $request, InstitutionalCsvImporter $importer): RedirectResponse
    {
        $account = $request->user()->institutionalAccount;

        abort_unless($account, 403);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $counts = $importer->import($data['file'], $account, $request->user()->id);

        $verb = $counts['created'].' listing(s) imported';

        return back()->with('success', $counts['failed'] > 0
            ? __(':verb — :failed row(s) failed and are recorded in the error report.', ['verb' => $verb, 'failed' => $counts['failed']])
            : __(':verb successfully. Every listing still enters the normal verification flow before going live.', ['verb' => $verb]));
    }
}
