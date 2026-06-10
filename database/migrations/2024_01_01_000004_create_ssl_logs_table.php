<?php
// FILE: database/migrations/2024_01_01_000004_create_ssl_logs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ssl_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('website_id');
            $table->boolean('ssl_valid')->default(false);
            $table->string('ssl_issuer', 255)->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->timestamps();

            $table->foreign('website_id')
                  ->references('website_id')
                  ->on('websites')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_logs');
    }
};
