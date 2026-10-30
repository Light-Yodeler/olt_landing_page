<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD', 'RahasiaAdmin2026!');
        $username = env('ADMIN_USERNAME', 'admin');

        if (blank($password) || strlen($password) < 12) {
            throw new RuntimeException('Isi ADMIN_PASSWORD di .env (minimal 12 karakter) sebelum menjalankan seeder.');
        }

        User::firstOrCreate(
            ['username' => $username],
            [
                'name' => 'Administrator',
                'password' => $password,
                'must_change_password' => true,
                'role' => 'superadmin',
            ],
        );
    }
}
