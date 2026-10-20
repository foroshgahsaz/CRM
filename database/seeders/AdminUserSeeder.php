<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('crm.seed_default_admin')) {
            return;
        }

        if (! Schema::hasTable('users') || ! Schema::hasTable('roles')) {
            return;
        }

        $email = (string) config('crm.default_admin.email');
        $password = (string) config('crm.default_admin.password');
        $name = (string) config('crm.default_admin.name');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'mobile' => null,
                'is_active' => true,
            ],
        );

        if (! $user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
        }
    }
}
