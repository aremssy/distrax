<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'Super Admin', 'guard_name' => 'web']
        );

        Role::firstOrCreate(
            ['name' => 'sub_admin'],
            ['display_name' => 'Sub Admin', 'guard_name' => 'web']
        );

        // Super admin gets all permissions
        $superAdmin->permissions()->sync(Permission::pluck('id'));
    }
}
