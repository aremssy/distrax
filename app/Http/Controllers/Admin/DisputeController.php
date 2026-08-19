<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateDisputeRequest;
use App\Models\Dispute;
use App\Services\DisputeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisputeController extends Controller
{
    public function index(Request $request): View
    {
        $disputes = Dispute::with(['raisedBy:id,name,email', 'againstUser:id,name,email', 'assignedTo:id,name,email', 'payment', 'refund'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('search')->value(), fn ($query, string $search) => $query->where(
                fn ($q) => $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('raisedBy', fn ($r) => $r->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
            ))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.disputes.index', compact('disputes'));
    }

    public function update(UpdateDisputeRequest $request, Dispute $dispute, DisputeResolver $resolver): RedirectResponse
    {
        $resolver->resolve($dispute, $request->validated());

        return redirect()->route('admin.disputes.index')->with('success', 'Dispute updated.');
    }
}
