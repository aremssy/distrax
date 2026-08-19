<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePayoutRequest;
use App\Http\Requests\Admin\UpdatePayoutRequest;
use App\Models\Payout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayoutController extends Controller
{
    public function index(Request $request)
    {
        $payouts = Payout::with(['user:id,name,email', 'processedBy:id,name,email'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('gateway')->value(), fn ($query, string $gateway) => $query->where('gateway', $gateway))
            ->latest()
            ->paginate(25);

        return view('admin.payouts.index', compact('payouts'));
    }

    public function store(StorePayoutRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['currency'] ??= setting('default_currency', 'BDT');
        $data['status'] = 'pending';

        Payout::create($data);

        return redirect()->route('admin.payouts.index')->with('success', 'Payout created successfully.');
    }

    /**
     * Allowed payout transitions. Completed is terminal (a replayed form must
     * never re-complete or "un-complete" a payout that already went out);
     * failed payouts may be retried.
     */
    private const TRANSITIONS = [
        'pending' => ['processing', 'completed', 'failed'],
        'processing' => ['completed', 'failed'],
        'failed' => ['processing'],
        'completed' => [],
    ];

    public function updateStatus(UpdatePayoutRequest $request, Payout $payout): RedirectResponse
    {
        $data = $request->validated();

        $conflict = DB::transaction(function () use ($data, $payout): bool {
            // Re-read under a write lock so two concurrent transitions can't both read
            // the same source status and both apply — the state machine must serialize.
            $payout = Payout::whereKey($payout->getKey())->lockForUpdate()->first();

            if (! in_array($data['status'], self::TRANSITIONS[$payout->status] ?? [], true)) {
                return true;
            }

            $payout->update([
                'status' => $data['status'],
                'admin_note' => $data['admin_note'] ?? $payout->admin_note,
                'processed_by' => auth()->id(),
                'processed_at' => in_array($data['status'], ['completed', 'failed'], true) ? now() : $payout->processed_at,
            ]);

            return false;
        });

        if ($conflict) {
            return back()->with('error', "A {$payout->status} payout cannot be changed to {$data['status']}.");
        }

        return redirect()->route('admin.payouts.index')->with('success', 'Payout status updated.');
    }
}
