<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount('users')
            ->orderBy('display_name')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permissions = Permission::orderBy('group')->orderBy('display_name')->get()
            ->groupBy('group');

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $role = Role::create([
                'name' => $validated['name'],
                'display_name' => $validated['display_name'],
                'guard_name' => $validated['guard_name'] ?? 'web',
            ]);

            if (! empty($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }
        });

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::orderBy('group')->orderBy('display_name')->get()
            ->groupBy('group');

        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($role, $validated): void {
            $role->update([
                'name' => $validated['name'],
                'display_name' => $validated['display_name'],
                'guard_name' => $validated['guard_name'] ?? 'web',
            ]);

            $role->permissions()->sync($validated['permissions'] ?? []);
        });

        // The permission set changed, so every member's cached permissions are now
        // stale — flush them immediately rather than waiting up to an hour for expiry.
        $role->users()->cursor()->each->clearPermissionCache();

        return redirect()->route('admin.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_unless(request()->user()->canManageRole($role), 403);

        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete a role that has users assigned.');
        }

        DB::transaction(function () use ($role): void {
            $role->permissions()->detach();
            $role->delete();
        });

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }
}
