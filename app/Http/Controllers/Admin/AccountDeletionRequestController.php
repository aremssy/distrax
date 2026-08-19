<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountDeletionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = User::query()
            ->whereNotNull('deletion_requested_at')
            ->with('role')
            ->oldest('deletion_requested_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.deletion-requests', compact('requests'));
    }

    /**
     * Approve the deletion request — permanently removes the account.
     */
    public function approve(Request $request, User $user): RedirectResponse
    {
        if (! $request->user()->canManageUser($user)) {
            return back()->with('error', 'You cannot delete a super admin.');
        }

        if (! $user->deletion_requested_at) {
            return back()->with('error', 'This user has no pending deletion request.');
        }

        $user->delete();

        return redirect()->route('admin.users.deletion-requests.index')->with('success', 'Account deleted.');
    }

    /**
     * Reject the deletion request — clears the flag and keeps the account active.
     */
    public function reject(User $user): RedirectResponse
    {
        $user->update(['deletion_requested_at' => null]);

        return redirect()->route('admin.users.deletion-requests.index')->with('success', 'Deletion request dismissed.');
    }
}
