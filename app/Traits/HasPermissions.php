<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait HasPermissions
{
    /**
     * Roles that only a super admin may assign, edit, or delete. These are the
     * privileged "root" roles; letting a lesser admin touch them would be a
     * privilege-escalation path.
     *
     * @var list<string>
     */
    private const PROTECTED_ROLES = ['super_admin'];

    public function isSuperAdmin(): bool
    {
        return $this->role?->name === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role?->name, ['super_admin', 'sub_admin']);
    }

    /**
     * May this user assign the given role to someone? Super admins can assign
     * any role; everyone else may assign only a non-protected role whose
     * permission set is fully contained within their own — so no admin can
     * grant (to another user or to themselves) access they do not hold.
     */
    public function canAssignRole(Role $role): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return false;
        }

        return $this->coversPermissions($role->permissions->pluck('name'));
    }

    /**
     * May this user create, edit, or delete the given role? Same ceiling as
     * assignment: protected roles are off-limits, and a role that already
     * carries permissions the actor lacks cannot be modified by them.
     */
    public function canManageRole(Role $role): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return false;
        }

        return $this->coversPermissions($role->permissions->pluck('name'));
    }

    /**
     * May this user modify the given target user? A non-super-admin can never
     * modify a super admin (block, demote, unverify, etc.).
     */
    public function canManageUser(User $target): bool
    {
        return $this->isSuperAdmin() || ! $target->isSuperAdmin();
    }

    /**
     * Does this user hold every one of the given permission names? Super admins
     * implicitly cover everything.
     *
     * @param  iterable<int, string>  $permissionNames
     */
    public function coversPermissions(iterable $permissionNames): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $held = $this->cachedPermissions();

        foreach ($permissionNames as $name) {
            if (! $held->contains($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Does this user hold every permission identified by the given IDs? Used to
     * stop an admin from granting a role more permissions than they hold.
     *
     * @param  iterable<int, int|string>  $permissionIds
     */
    public function coversPermissionIds(iterable $permissionIds): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $ids = collect($permissionIds)->filter()->values();

        if ($ids->isEmpty()) {
            return true;
        }

        return $this->coversPermissions(Permission::whereIn('id', $ids)->pluck('name'));
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->cachedPermissions()->contains($permission);
    }

    public function hasAnyPermission(string ...$permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $cached = $this->cachedPermissions();

        foreach ($permissions as $permission) {
            if ($cached->contains($permission)) {
                return true;
            }
        }

        return false;
    }

    public function cachedPermissions(): Collection
    {
        return Cache::remember(
            "user_permissions_{$this->id}",
            now()->addHour(),
            fn () => $this->loadPermissionNames()
        );
    }

    public function clearPermissionCache(): void
    {
        Cache::forget("user_permissions_{$this->id}");
    }

    private function loadPermissionNames(): Collection
    {
        $rolePermissions = Permission::query()
            ->join('permission_role', 'permissions.id', '=', 'permission_role.permission_id')
            ->where('permission_role.role_id', $this->role_id)
            ->pluck('permissions.name');

        $directPermissions = Permission::query()
            ->join('permission_user', 'permissions.id', '=', 'permission_user.permission_id')
            ->where('permission_user.user_id', $this->id)
            ->pluck('permissions.name');

        return $rolePermissions->merge($directPermissions)->unique()->values();
    }
}
