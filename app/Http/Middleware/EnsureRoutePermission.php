<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
    /**
     * Map conventional resource actions to application permission actions.
     *
     * @var array<string, string>
     */
    private const RESOURCE_ACTIONS = [
        'index' => 'view',
        'show' => 'view',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'update',
        'update' => 'update',
        'destroy' => 'delete',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        abort_if(blank($routeName), 403, 'This route is not configured for access.');

        $permission = $this->permissionFor($routeName);

        try {
            Permission::findByName($permission, 'web');
        } catch (PermissionDoesNotExist) {
            abort(403, 'This route is not configured for access.');
        }

        abort_unless(
            $request->user()?->can($permission),
            403,
            'You do not have permission to perform this action.',
        );

        return $next($request);
    }

    private function permissionFor(string $routeName): string
    {
        if (! str_contains($routeName, '.')) {
            return "{$routeName}.view";
        }

        $action = Str::afterLast($routeName, '.');
        $module = Str::beforeLast($routeName, '.');

        if (array_key_exists($action, self::RESOURCE_ACTIONS)) {
            return $module.'.'.self::RESOURCE_ACTIONS[$action];
        }

        return $routeName;
    }
}
