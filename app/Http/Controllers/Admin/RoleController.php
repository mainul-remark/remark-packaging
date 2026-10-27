<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        $rows = Role::query()
            ->whereNotIn('name', ['developer', 'super-admin'])
            ->withCount('permissions')
            ->latest()
            ->get();

        return view('admin.roles.index', compact('rows'));
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRole($request);

        DB::transaction(function () use ($validated): void {
            $role = Role::create([
                'name' => Str::slug($validated['name']),
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($this->permissionsFor($validated['permissions']));
        });

        return redirect()->route('roles.index')->with([
            'message' => 'Role created successfully!',
            'alert-type' => 'success',
        ]);
    }

    public function show(Role $role): RedirectResponse
    {
        return redirect()->route('roles.edit', $role);
    }

    public function edit(Role $role): View
    {
        abort_if($this->isProtected($role), 403, 'System roles cannot be edited.');

        return view('admin.roles.edit', [
            'role' => $role,
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => $role->permissions->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($this->isProtected($role), 403, 'System roles cannot be edited.');

        $validated = $this->validateRole($request, $role);

        DB::transaction(function () use ($role, $validated): void {
            $role->update([
                'name' => Str::slug($validated['name']),
            ]);
            $role->syncPermissions($this->permissionsFor($validated['permissions']));
        });

        return redirect()->route('roles.index')->with([
            'message' => 'Role updated successfully!',
            'alert-type' => 'success',
        ]);
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($this->isProtected($role), 403, 'System roles cannot be deleted.');

        $role->delete();

        return redirect()->route('roles.index')->with([
            'message' => 'Role deleted successfully!',
            'alert-type' => 'success',
        ]);
    }

    /** @return array<string, mixed> */
    private function validateRole(Request $request, ?Role $role = null): array
    {
        $request->merge([
            'name' => Str::slug((string) $request->input('name')),
        ]);

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->ignore($role?->id),
            ],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => [
                'integer',
                'distinct',
                Rule::exists('permissions', 'id')->where('guard_name', 'web'),
            ],
        ]);
    }

    /** @return Collection<string, Collection<int, Permission>> */
    private function permissionGroups(): Collection
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission): string => Str::headline(Str::before($permission->name, '.'))
            );
    }

    /**
     * Spatie treats string values as names, so form IDs must be resolved to models.
     *
     * @param  array<int, int|string>  $ids
     * @return Collection<int, Permission>
     */
    private function permissionsFor(array $ids): Collection
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('id', $ids)
            ->get();
    }

    private function isProtected(Role $role): bool
    {
        return in_array($role->name, ['developer', 'super-admin'], true);
    }
}
