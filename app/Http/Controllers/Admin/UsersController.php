<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if (! $request->ajax()) {
            return view('admin.users.index');
        }

        $users = User::query()
            ->with('roles:id,name')
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['developer', 'super-admin'])
            )
            ->select(['id', 'name', 'email', 'employee_id', 'profile_photo_path', 'is_active', 'created_at'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $users->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'profile_photo_url' => $user->profile_photo_url,
                'role_names' => $user->roles->pluck('name')->implode(', '),
                'created_at' => $user->created_at?->format('Y-m-d'),
                'is_active' => (int) $user->is_active,
            ])->values(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);

        $user = DB::transaction(function () use ($request, $validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'employee_id' => $validated['employee_id'],
                'password' => $validated['password'],
                'is_active' => $validated['is_active'],
            ]);

            $user->syncRoles($this->rolesFor($validated['roles']));

            if ($request->hasFile('profile_photo')) {
                $user->updateProfilePhoto($request->file('profile_photo'));
            }

            return $user;
        });

        return redirect()->route('users.index')->with([
            'message' => "{$user->name} was created successfully!",
            'alert-type' => 'success',
        ]);
    }

    public function show(User $user): View
    {
        $user->load('roles:id,name');

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        abort_if($user->hasAnyRole(['developer', 'super-admin']), 403, 'System accounts cannot be edited here.');

        $user->load('roles:id,name');

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $this->assignableRoles(),
            'selectedRoleIds' => $user->roles->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->hasAnyRole(['developer', 'super-admin']), 403, 'System accounts cannot be edited here.');

        $validated = $this->validateUser($request, $user);

        DB::transaction(function () use ($request, $validated, $user): void {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'employee_id' => $validated['employee_id'],
                'is_active' => $validated['is_active'],
            ]);

            if (! empty($validated['password'])) {
                $user->password = $validated['password'];
            }

            $user->save();
            $user->syncRoles($this->rolesFor($validated['roles']));

            if ($request->hasFile('profile_photo')) {
                $user->updateProfilePhoto($request->file('profile_photo'));
            }
        });

        return redirect()->route('users.index')->with([
            'message' => 'User updated successfully!',
            'alert-type' => 'success',
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->is(Auth::user())) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        if ($user->hasAnyRole(['developer', 'super-admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'System accounts cannot be deleted.',
            ], 422);
        }

        $user->deleteProfilePhoto();
        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully!',
        ]);
    }

    public function profile(): View
    {
        return view('admin.users.profile', ['user' => Auth::user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'employee_id' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ]);

        $user->update($validated);

        if ($request->hasFile('profile_photo')) {
            $user->updateProfilePhoto($request->file('profile_photo'));
        }

        return back()->with([
            'message' => 'Profile updated successfully!',
            'alert-type' => 'success',
        ]);
    }

    public function pwChange(): View
    {
        return view('admin.users.password');
    }

    public function pwUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'old_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Password changed successfully.');
    }

    /** @return array<string, mixed> */
    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'employee_id' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'is_active' => ['required', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'integer',
                'distinct',
                Rule::exists('roles', 'id')
                    ->where('guard_name', 'web')
                    ->whereNotIn('name', ['developer', 'super-admin']),
            ],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ]);
    }

    /**
     * Spatie treats string values as names, so form IDs must be resolved to models.
     *
     * @param  array<int, int|string>  $ids
     * @return Collection<int, Role>
     */
    private function rolesFor(array $ids): Collection
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->whereIn('id', $ids)
            ->get();
    }

    /** @return Collection<int, Role> */
    private function assignableRoles(): Collection
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', ['developer', 'super-admin'])
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
