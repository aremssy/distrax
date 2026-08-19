<?php

namespace App\Http\Controllers\Website\RentManagement;

use App\Http\Controllers\Concerns\GatesRentManagementFeatures;
use App\Http\Controllers\Controller;
use App\Models\LedgerTransaction;
use App\Models\Tenancy;
use App\Services\PostEntitlementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RentManagementController extends Controller
{
    use GatesRentManagementFeatures;

    public function index(Request $request, PostEntitlementService $entitlement): View
    {
        $user = $request->user();
        $features = $entitlement->unlockedFeatures($user);
        $hasAccess = $this->hasRentManagementAccess($features);

        $tenancies = null;
        $summary = null;

        if ($hasAccess) {
            $tenancies = Tenancy::where('owner_id', $user->id)
                ->with(['listing:id,title', 'unit:id,name', 'tenant:id,name'])
                ->withCount(['rentPayments as overdue_payments_count' => fn ($q) => $q->where('status', 'overdue')])
                ->orderByDesc('created_at')
                ->paginate(10);

            $summary = [
                'income' => (int) LedgerTransaction::where('owner_id', $user->id)->where('type', 'income')->sum('amount'),
                'expense' => (int) LedgerTransaction::where('owner_id', $user->id)->where('type', 'expense')->sum('amount'),
            ];
        }

        return view('website.owner.rent-management.index', [
            'features' => $features,
            'hasAccess' => $hasAccess,
            'tenancies' => $tenancies,
            'summary' => $summary,
        ]);
    }
}
