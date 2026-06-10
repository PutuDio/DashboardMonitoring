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
     * Catatan: User admin default (username: Admin2, password: Samanta123)
     * sudah dibuat secara otomatis via migration 2024_01_01_000001_create_users_table.php.
     *
     * Seeder ini bisa dipakai untuk mengisi data pengujian tambahan.
     */
    public function run(): void
    {
        // Buat 2 user operator dan 2 viewer untuk data dummy (hanya di dev/testing)
        if (app()->isLocal()) {
            User::factory()->operator()->count(2)->create();
            User::factory()->viewer()->count(2)->create();
        }
    }
}
