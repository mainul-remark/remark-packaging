<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync {--user=1 : Id of the user who receives every permission}';

    protected $description = 'Inserts missing permissions from RoleTableSeeder and config/custom-permissions.php, then assigns all permissions to user 1.';

    public function handle(): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = collect(RoleTableSeeder::permissions())
            ->merge(config('custom-permissions', []))
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique()
            ->values();

        $created = 0;

        foreach ($names as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $created += $permission->wasRecentlyCreated ? 1 : 0;
        }

        $user = User::query()->find((int) $this->option('user'));

        if ($user === null) {
            $this->warn("User id {$this->option('user')} not found; permissions were not assigned.");
        } else {
            $user->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("{$names->count()} permissions processed, {$created} created.");

        if ($user !== null) {
            $this->info("All permissions assigned to user #{$user->id} ({$user->email}).");
        }

        return $user === null ? self::FAILURE : self::SUCCESS;
    }
}
