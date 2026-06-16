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

        // ادمین اصلی پروژه (super-admin). firstOrCreate رمزِ کاربرِ موجود را تغییر نمی‌دهد؛
        // مقدار زیر فقط روی دیتابیس کاملاً تازه استفاده می‌شود.
        User::firstOrCreate(
            ['email' => 'yaldaj.619@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', \Illuminate\Support\Str::random(16))),
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
