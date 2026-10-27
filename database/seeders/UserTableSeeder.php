<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    public function run(): void
    {
        $developer = User::updateOrCreate(
            ['email' => 'developer@remarkhb.com'],
            [
                'name' => 'Developer',
                'employee_id' => 'M7005',
                'password' => bcrypt('developer@remarkhb.com'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        $developer->syncRoles(['developer']);

        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@remarkhb.com'],
            [
                'name' => 'Super Admin',
                'employee_id' => 'N7006',
                'password' => 'password',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        $superAdmin->syncRoles(['super-admin']);
    }
}
