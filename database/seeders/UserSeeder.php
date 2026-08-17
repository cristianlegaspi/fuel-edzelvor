<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed login-able demo users for the fuel-edzelvor Blade UI, one per
     * role, so the RBAC/user-management behaviour can be exercised right
     * after a fresh install. Change these passwords before going to
     * production.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'role' => User::ROLE_SUPERADMIN,
                'is_active' => true,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Demo Admin',
                'password' => bcrypt('password'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Demo Staff',
                'password' => bcrypt('password'),
                'role' => User::ROLE_STAFF,
                'is_active' => true,
            ]
        );
    }
}
