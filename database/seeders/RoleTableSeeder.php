<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleTableSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'activity-log.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $allPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->get();

        Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'developer',
            'guard_name' => 'web',
        ])->syncPermissions($allPermissions);

        Role::firstOrCreate([
            'name' => 'administrator',
            'guard_name' => 'web',
        ])->syncPermissions($allPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
