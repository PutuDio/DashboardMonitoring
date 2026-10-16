<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Catatan: User admin default (username: admin, password: password)
     * sudah dibuat secara otomatis via migration 2024_01_01_000001_create_users_table.php.
     */
    public function run(): void
    {
        // Buat akun testing operator dan viewer dengan password 'password'
        User::firstOrCreate(
            ['username' => 'operator'],
            [
                'password_hash' => \Illuminate\Support\Facades\Hash::make('password'),
                'full_name'     => 'Petugas Operator',
                'role'          => 'operator',
            ]
        );

        User::firstOrCreate(
            ['username' => 'viewer'],
            [
                'password_hash' => \Illuminate\Support\Facades\Hash::make('password'),
                'full_name'     => 'Petugas Viewer',
                'role'          => 'viewer',
            ]
        );
    }
}
