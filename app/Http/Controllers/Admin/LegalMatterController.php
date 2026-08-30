<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalMatter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalMatterController extends Controller
{
    public function index(Request $request): View
    {
        $matters = LegalMatter::with(['deal.listing:id,title', 'assignee:id,name'])
            ->when($request->string('status')->value(), fn ($q, $s) => $q->where('status', $s))
            ->when($request->boolean('open'), fn ($q) => $q->whereIn('status', ['pending', 'in_review', 'issue_found']))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.legal-matters.index', compact('matters'));
    }

    public function show(LegalMatter $matter): View
    {
        $matter->load(['deal.listing:id,title,slug', 'deal.buyer:id,name', 'deal.seller:id,name', 'assignee:id,name']);

        $reviewers = User::whereHas('role', fn ($q) => $q->whereIn('name', ['legal_reviewer', 'super_admin', 'sub_admin']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.legal-matters.show', compact('matter', 'reviewers'));
    }

    public function update(Request $request, LegalMatter $matter): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_review,cleared,issue_found'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $matter->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? $matter->assigned_to,
            'resolved_at' => $validated['status'] === 'cleared' ? now() : null,
        ]);

        return back()->with('success', 'Legal matter updated.');
    }
}
