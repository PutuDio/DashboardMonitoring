<?php
// ============================================================
// FILE: database/migrations/2024_01_01_000001_create_users_table.php
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('username', 50)->unique();
            $table->string('password_hash');
            $table->string('full_name', 100);
            $table->enum('role', ['admin', 'operator', 'viewer'])->default('operator');
            $table->timestamp('last_login')->nullable();
            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

// ============================================================
// FILE: database/migrations/2024_01_01_000002_create_websites_table.php
// ============================================================
// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration {
//     public function up(): void
//     {
//         Schema::create('websites', function (Blueprint $table) {
//             $table->id('website_id');
//             $table->string('name', 200);
//             $table->string('url');
//             $table->unsignedInteger('check_interval_minutes')->default(5);
//             $table->enum('status', ['active', 'nonactive'])->default('active');
//             $table->timestamp('last_checked')->nullable();
//             $table->timestamps();
//         });
//     }
//     public function down(): void { Schema::dropIfExists('websites'); }
// };

// ============================================================
// FILE: database/migrations/2024_01_01_000003_create_incidents_table.php
// ============================================================
// return new class extends Migration {
//     public function up(): void
//     {
//         Schema::create('incidents', function (Blueprint $table) {
//             $table->id('incident_id');
//             $table->unsignedBigInteger('website_id');
//             $table->string('type', 100);
//             $table->enum('severity', ['low', 'medium', 'high', 'critical']);
//             $table->enum('status', ['Open', 'Resolved'])->default('Open');
//             $table->text('description')->nullable();
//             $table->json('metadata')->nullable();
//             $table->unsignedBigInteger('snapshot_before_id')->nullable();
//             $table->longText('snapshot_after')->nullable();
//             $table->timestamp('resolved_at')->nullable();
//             $table->timestamps();
//             $table->foreign('website_id')->references('website_id')->on('websites')->onDelete('cascade');
//         });
//     }
//     public function down(): void { Schema::dropIfExists('incidents'); }
// };

// ============================================================
// FILE: database/migrations/2024_01_01_000004_create_uptime_logs_table.php
// ============================================================
// return new class extends Migration {
//     public function up(): void
//     {
//         Schema::create('uptime_logs', function (Blueprint $table) {
//             $table->id();
//             $table->unsignedBigInteger('website_id');
//             $table->unsignedInteger('http_status')->nullable();
//             $table->unsignedInteger('response_time_ms')->nullable();
//             $table->timestamps();
//             $table->foreign('website_id')->references('website_id')->on('websites')->onDelete('cascade');
//         });
//     }
//     public function down(): void { Schema::dropIfExists('uptime_logs'); }
// };

// ============================================================
// FILE: database/migrations/2024_01_01_000005_create_content_snapshots_table.php
// ============================================================
// return new class extends Migration {
//     public function up(): void
//     {
//         Schema::create('content_snapshots', function (Blueprint $table) {
//             $table->id();
//             $table->unsignedBigInteger('website_id');
//             $table->longText('html');
//             $table->string('content_hash', 64);
//             $table->timestamps();
//             $table->foreign('website_id')->references('website_id')->on('websites')->onDelete('cascade');
//         });
//     }
//     public function down(): void { Schema::dropIfExists('content_snapshots'); }
// };
