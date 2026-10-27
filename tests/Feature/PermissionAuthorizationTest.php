<?php

use App\Models\User;
use Database\Seeders\RoleTableSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RoleTableSeeder::class);
});

test('authenticated users without permission cannot open protected admin resources', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('role permissions grant access through Laravel can middleware', function () {
    $user = User::factory()->create();
    $user->assignRole('administrator');

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertOk();
});

test('super admin bypasses individual permission assignments', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $this->actingAs($user)
        ->get(route('roles.index'))
        ->assertOk();
});

test('route permission middleware fails closed when a permission is not configured', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');
    Permission::findByName('roles.view')->delete();

    $this->actingAs($user)
        ->get(route('roles.index'))
        ->assertForbidden();
});

test('single segment route names resolve to view permissions', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('dashboard.view');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('authorized users can create roles with permissions', function () {
    $user = User::factory()->create();
    $user->assignRole('administrator');
    $permission = Permission::findByName('dashboard.view');

    $this->actingAs($user)
        ->post(route('roles.store'), [
            'name' => 'Production Manager',
            'permissions' => [$permission->id],
        ])
        ->assertRedirect(route('roles.index'));

    expect(Role::findByName('production-manager', 'web')->hasPermissionTo('dashboard.view'))->toBeTrue();
});

test('authorized users can create users and assign Spatie roles', function () {
    $administrator = User::factory()->create();
    $administrator->assignRole('administrator');
    $role = Role::create(['name' => 'production-operator', 'guard_name' => 'web']);

    $this->actingAs($administrator)
        ->post(route('users.store'), [
            'name' => 'Production Operator',
            'email' => 'operator@example.com',
            'employee_id' => 'EMP-1001',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_active' => 1,
            'roles' => [$role->id],
        ])
        ->assertRedirect(route('users.index'));

    expect(User::where('email', 'operator@example.com')->firstOrFail()->hasRole('production-operator'))->toBeTrue();
});
