<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending')->index();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['property_listing_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_schedules');
    }
};
