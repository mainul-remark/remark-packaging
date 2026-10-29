<?php

use App\Http\Middleware\EnsureRoutePermission;
use App\Models\User;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Http\Request;
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

test('every operational page route requires its permission', function (string $routeName, string $permission) {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route($routeName))->assertForbidden();

    $user->givePermissionTo($permission);

    expect($this->actingAs($user)->get(route($routeName))->status())->not->toBe(403);
})->with([
    ['orders.index', 'orders.view'],
    ['products.index', 'products.view'],
    ['brands.index', 'brands.view'],
    ['product-categories.index', 'product-categories.view'],
    ['package-station', 'package-station.view'],
    ['package-station.data', 'package-station.view'],
    ['carton-cross-check', 'carton-cross-check.view'],
    ['cartons.index', 'cartons.view'],
]);

test('every protected route resolves to a seeded permission', function () {
    $protected = collect(app('router')->getRoutes()->getRoutesByName())
        ->filter(fn ($route) => in_array('route.permission', $route->gatherMiddleware(), true));

    expect($protected)->not->toBeEmpty();

    $protected->each(function ($route, string $name) {
        $this->actingAs(User::factory()->create()->assignRole('administrator'));

        // administrator holds every seeded permission, so a 403 here means the permission is missing
        $request = Request::create('/');
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => auth()->user());

        $response = (new EnsureRoutePermission)->handle($request, fn () => response('ok'));

        expect($response->getContent())->toBe('ok', "Route [{$name}] has no seeded permission.");
    });
});

test('permissions:sync inserts seeder and config permissions and assigns all to user 1', function () {
    config(['custom-permissions' => ['reports.view']]);
    $user = User::factory()->create(['id' => 1]);

    $this->artisan('permissions:sync')->assertSuccessful();
    $this->artisan('permissions:sync')->assertSuccessful();

    expect(Permission::where('name', 'reports.view')->count())->toBe(1)
        ->and($user->fresh()->hasDirectPermission('reports.view'))->toBeTrue()
        ->and($user->fresh()->hasDirectPermission('dashboard.view'))->toBeTrue();
});
