<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminManagerSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the role exists (it's also in RoleSeeder)
        $role = Role::where('name', 'admin_manager')->first();

        if (! $role) {
            $role = Role::create([
                'name' => 'admin_manager',
                'display_name_en' => 'Administrative Manager',
                'display_name_ar' => 'المدير الإداري',
                'permissions' => [],
                'is_system' => false,
            ]);
        }

        // Create the admin manager user
        User::updateOrCreate(
            ['email' => 'admin@doctorato.com'],
            [
                'name' => 'Administrative Manager',
                'username' => 'manager',
                'email' => 'admin@doctorato.com',
                'password' => Hash::make('Manager@2026'),
                'role_id' => $role->id,
                'is_active' => true,
            ]
        );
    }
}
