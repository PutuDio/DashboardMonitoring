<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id('website_id');
            $table->string('name', 200);
            $table->string('url');
            $table->unsignedInteger('check_interval_minutes')->default(5);
            $table->enum('status', ['active', 'nonactive'])->default('active');
            $table->timestamp('last_checked')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};
