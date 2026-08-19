<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bio')->nullable();
            $table->json('skills')->nullable();
            $table->json('available_time')->nullable();
            $table->unsignedSmallInteger('experience_years')->default(0);
            $table->unsignedBigInteger('hourly_rate')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->enum('status', ['pending', 'active', 'inactive', 'suspended'])->default('pending')->index();
            $table->softDeletes();
            $table->timestamps();
            // Marketplace: WHERE status/is_verified/is_available ORDER BY rating DESC.
            $table->index(['status', 'is_verified', 'is_available'], 'technicians_status_verified_available_index');
            $table->index('rating', 'technicians_rating_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technicians');
    }
};
