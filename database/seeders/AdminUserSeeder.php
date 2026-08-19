<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('name', 'super_admin')->firstOrFail();

        User::firstOrCreate(
            ['email' => 'admin@rentdo.com'],
            [
                'role_id' => $role->id,
                'name' => 'Super Admin',
                'password' => Hash::make('admin@123'),
                'verification_status' => 'verified',
                'email_verified_at' => now(),
            ]
        );
    }
}
