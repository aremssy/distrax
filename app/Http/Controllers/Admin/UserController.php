<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\NotificationCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search');
        $roleId = $request->integer('role_id');
        $verificationStatus = $request->string('verification_status');
        $blocked = $request->string('is_blocked');

        $users = User::with('role')
            ->when($search->isNotEmpty(), fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($roleId, fn ($q, $id) => $q->where('role_id', $id))
            ->when($verificationStatus->isNotEmpty(), fn ($q) => $q->where('verification_status', $verificationStatus->value()))
            ->when($blocked->isNotEmpty(), fn ($q) => $q->where('is_blocked', $blocked->value() === '1'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $roles = Role::orderBy('display_name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function show(User $user): View
    {
        $user->load([
            'role',
            'wallet',
            'listings' => fn ($q) => $q->with('zone:id,name')->latest()->limit(10),
            'subscriptions' => fn ($q) => $q->with('plan')->latest()->limit(10),
            'reviews' => fn ($q) => $q->with('reviewable')->latest()->limit(10),
        ]);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $roles = Role::orderBy('display_name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $validated['is_blocked'] = $request->boolean('is_blocked');
        $wasBlocked = $user->is_blocked;
        $previousRoleId = $user->role_id;
        $previousVerification = $user->verification_status;

        if ($request->hasFile('avatar')) {
            $oldAvatar = $user->avatar;
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($validated['avatar']);
        }

        // Stamp the reviewer when this edit changes the verification decision, so the
        // audit fields stay in sync with the verification screen's own bookkeeping.
        if (array_key_exists('verification_status', $validated)
            && $validated['verification_status'] !== $previousVerification) {
            $validated['verification_reviewed_by'] = $request->user()->id;
            $validated['verification_reviewed_at'] = now();
        }

        $user->update($validated);

        // A role change swaps the whole permission set — drop the stale cache now so the
        // new access (or revocation) takes effect on the very next request.
        if ($user->role_id !== $previousRoleId) {
            $user->clearPermissionCache();
        }

        if (isset($oldAvatar) && $oldAvatar && ! str_starts_with($oldAvatar, 'http')) {
            Storage::disk('public')->delete($oldAvatar);
        }

        // Tell the user when their access is blocked or restored.
        if ($wasBlocked !== $user->is_blocked) {
            app(NotificationCenter::class)->toUser(
                user: $user,
                type: 'system',
                title: $user->is_blocked ? 'Your account has been blocked' : 'Your account has been restored',
                body: $user->is_blocked
                    ? 'Access to your account has been suspended. Contact support if you believe this is a mistake.'
                    : 'Your account access has been restored. Welcome back!',
                subject: $user,
            );
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if (! $request->user()->canManageUser($user)) {
            return back()->with('error', 'You cannot delete a super admin.');
        }

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
