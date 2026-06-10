<?php
// ============================================================
// FILE: database/migrations/2024_01_01_000001_create_users_table.php
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('username', 50)->unique();
            $table->string('password_hash');        // kompatibel dengan password lama
            $table->string('full_name', 100);
            $table->enum('role', ['admin', 'operator', 'viewer'])->default('operator');
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
        });

        // Buat user admin default (username: Admin2, password: Samanta123)
        // Sesuai kredensial di db.php lama
        DB::table('users')->insert([
            'username'      => 'Admin2',
            'password_hash' => Hash::make('Samanta123'),
            'full_name'     => 'Administrator',
            'role'          => 'admin',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
