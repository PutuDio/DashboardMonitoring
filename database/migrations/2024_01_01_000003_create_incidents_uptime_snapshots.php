<?php
// FILE: 2024_01_01_000003_create_incidents_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id('incident_id');
            $table->unsignedBigInteger('website_id');
            $table->string('type', 100);
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->enum('status', ['Open', 'Resolved'])->default('Open');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('snapshot_before_id')->nullable();
            $table->longText('snapshot_after')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('website_id')
                  ->references('website_id')
                  ->on('websites')
                  ->onDelete('cascade');
        });

        Schema::create('uptime_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('website_id');
            $table->unsignedInteger('http_status')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamps();

            $table->foreign('website_id')
                  ->references('website_id')
                  ->on('websites')
                  ->onDelete('cascade');
        });

        Schema::create('content_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('website_id');
            $table->longText('html');
            $table->string('content_hash', 64);
            $table->timestamps();

            $table->foreign('website_id')
                  ->references('website_id')
                  ->on('websites')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_snapshots');
        Schema::dropIfExists('uptime_logs');
        Schema::dropIfExists('incidents');
    }
};
