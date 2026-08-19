<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePasswordRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('admin.profile.edit', ['user' => auth()->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            $oldAvatar = $user->avatar;
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($validated['avatar']);
        }

        $user->update($validated);

        if (isset($oldAvatar) && $oldAvatar && ! str_starts_with($oldAvatar, 'http')) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return redirect()->route('admin.profile.edit')->with('success', 'Profile updated.');
    }

    public function password(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return redirect()->route('admin.profile.edit')->with('success', 'Password updated.');
    }
}
