<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // ادمین اصلی پروژه (super-admin) — رمز را پس از اولین ورود تغییر دهید.
        User::firstOrCreate(
            ['email' => 'admin@homeh.com'],
            [
                'name' => 'Rangify Admin',
                'password' => Hash::make('Rangify@2026'),
            ]
        )->assignRole('super-admin');

        // کاربر تستی نمونه.
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ]
        )->assignRole('user');
    }
}
