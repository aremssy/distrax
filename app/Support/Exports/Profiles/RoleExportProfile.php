<?php

namespace App\Support\Exports\Profiles;

use App\Models\Role;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class RoleExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'roles';
    }

    public function permission(): string
    {
        return 'roles.view';
    }

    public function title(): string
    {
        return 'Roles';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'display_name' => 'Name',
            'name' => 'Slug',
            'guard_name' => 'Guard',
            'users_count' => 'Users',
            'created_at' => 'Created',
        ];
    }

    /**
     * Mirrors RoleController@index (no request filters).
     */
    public function query(Request $request): Builder
    {
        return Role::withCount('users')
            ->orderBy('display_name');
    }

    /**
     * @param  Role  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'display_name' => $row->display_name,
            'name' => $row->name,
            'guard_name' => $row->guard_name,
            'users_count' => $row->users_count,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
