<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\PermissionList;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionList::grouped() as $group => $slugs) {
            foreach ($slugs as $slug) {
                Permission::firstOrCreate(
                    ['name' => $slug],
                    [
                        'display_name' => ucwords(str_replace(['.', '_'], ' ', $slug)),
                        'group' => $group,
                        'guard_name' => 'web',
                    ]
                );
            }
        }
    }
}
