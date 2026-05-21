<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * سه نقش پروژه Rangify: super-admin، client-admin، user.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'manage tenants',
            'manage users',
            'view dashboard',
            'manage projects',
            'run segmentation',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        Role::firstOrCreate(['name' => 'super-admin'])
            ->syncPermissions($permissions);

        Role::firstOrCreate(['name' => 'client-admin'])
            ->syncPermissions([
                'manage users',
                'view dashboard',
                'manage projects',
                'run segmentation',
            ]);

        Role::firstOrCreate(['name' => 'user'])
            ->syncPermissions([
                'view dashboard',
                'manage projects',
                'run segmentation',
            ]);
    }
}
