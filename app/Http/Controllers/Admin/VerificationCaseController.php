<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationCase;
use App\Models\VerificationEvidence;
use App\Models\VerificationTask;
use App\Services\VerificationCaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerificationCaseController extends Controller
{
    public function index(Request $request): View
    {
        $cases = VerificationCase::with(['listing:id,title,slug,status', 'officer:id,name'])
            ->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->boolean('mine'), fn ($q) => $q->where('assigned_officer_id', auth()->id()))
            ->latest('opened_at')
            ->paginate(25)
            ->withQueryString();

        $officers = User::whereHas('role', fn ($q) => $q->whereIn('name', ['verification_officer', 'super_admin', 'sub_admin']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.verification-cases.index', compact('cases', 'officers'));
    }

    public function show(VerificationCase $case): View
    {
        $case->load(['listing', 'officer:id,name', 'tasks.assignee:id,name', 'tasks.evidence', 'scores']);

        $officers = User::whereHas('role', fn ($q) => $q->whereIn('name', ['verification_officer', 'super_admin', 'sub_admin']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.verification-cases.show', compact('case', 'officers'));
    }

    public function assign(Request $request, VerificationCase $case, VerificationCaseService $service): RedirectResponse
    {
        $validated = $request->validate(['officer_id' => ['required', 'exists:users,id']]);

        $service->assignOfficer($case, User::findOrFail($validated['officer_id']), $request->user());

        return back()->with('success', 'Case assigned.');
    }

    public function updateTask(Request $request, VerificationCase $case, VerificationTask $task, VerificationCaseService $service): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:not_started,in_progress,passed,failed,flagged'],
            'notes' => ['nullable', 'string'],
            'waived' => ['sometimes', 'boolean'],
        ]);

        try {
            $service->recordTaskOutcome(
                $task,
                $validated['status'],
                $validated['notes'] ?? null,
                $request->user(),
                (bool) ($validated['waived'] ?? false),
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Task updated.');
    }

    public function uploadEvidence(Request $request, VerificationCase $case, VerificationTask $task, VerificationCaseService $service): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $service->attachEvidence($task, $validated['type'], $request->file('file'), $validated['description'] ?? null, $request->user());

        return back()->with('success', 'Evidence uploaded.');
    }

    /** Stream evidence from the private disk \u2014 never exposed via a public URL. */
    public function evidenceFile(VerificationEvidence $evidence): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($evidence->file_path), 404);

        return Storage::disk('local')->response($evidence->file_path);
    }
}
