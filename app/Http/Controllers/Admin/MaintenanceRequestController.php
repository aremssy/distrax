<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMaintenanceRequestRequest;
use App\Models\MaintenanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = MaintenanceRequest::with(['tenant:id,name,email', 'owner:id,name,email', 'technician.user:id,name,email', 'listing:id,title'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('priority')->value(), fn ($query, string $priority) => $query->where('priority', $priority))
            ->latest()
            ->paginate(25);

        return view('admin.maintenance-requests.index', ['maintenanceRequests' => $requests]);
    }

    public function update(UpdateMaintenanceRequestRequest $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $data = $request->validated();

        if (in_array($data['status'], ['completed', 'closed'], true)) {
            $data['resolved_at'] = now();
        }

        $maintenanceRequest->update($data);

        return redirect()->route('admin.maintenance-requests.index')->with('success', 'Maintenance request updated.');
    }
}
