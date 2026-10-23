<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the default store administrator account.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@isaleeeko.ca'],
            [
                'name' => 'Store Manager',
                'password' => Hash::make('password123'),
                'is_admin' => true,
                'role' => User::ROLE_MANAGER,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
