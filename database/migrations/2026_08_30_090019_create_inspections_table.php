<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspector_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled')->index();
            $table->json('checklist')->nullable(); // structured checklist items + pass/fail
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_lng', 10, 7)->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['property_listing_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
