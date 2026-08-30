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

        Role::firstOrCreate(
            ['name' => 'verification_officer'],
            ['display_name' => 'Verification Officer', 'guard_name' => 'web']
        );

        Role::firstOrCreate(
            ['name' => 'deal_manager'],
            ['display_name' => 'Deal Manager', 'guard_name' => 'web']
        );

        Role::firstOrCreate(
            ['name' => 'legal_reviewer'],
            ['display_name' => 'Legal Reviewer', 'guard_name' => 'web']
        );

        Role::firstOrCreate(
            ['name' => 'inspector'],
            ['display_name' => 'Inspector', 'guard_name' => 'web']
        );

        Role::firstOrCreate(
            ['name' => 'moderator'],
            ['display_name' => 'Moderator', 'guard_name' => 'web']
        );

        Role::firstOrCreate(
            ['name' => 'finance'],
            ['display_name' => 'Finance', 'guard_name' => 'web']
        );

        $dealManager = Role::where('name', 'deal_manager')->first();
        if ($dealManager) {
            $dealManager->permissions()->sync(Permission::whereIn('name', [
                'deals.view', 'deals.edit', 'deals.advance', 'deals.cancel',
                'offers.view', 'inspections.view', 'inspections.assign',
                'legal_matters.view',
            ])->pluck('id'));
        }

        $legalReviewer = Role::where('name', 'legal_reviewer')->first();
        if ($legalReviewer) {
            $legalReviewer->permissions()->sync(Permission::whereIn('name', [
                'legal_matters.view', 'legal_matters.edit', 'legal_matters.resolve',
                'deals.view',
            ])->pluck('id'));
        }

        // Super admin gets all permissions
        $superAdmin->permissions()->sync(Permission::pluck('id'));
    }
}
