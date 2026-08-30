<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('old_price')->nullable();
            $table->unsignedBigInteger('new_price');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->char('currency_code', 3)->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['property_listing_id', 'changed_at']);
        });

        Schema::create('property_timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->enum('event_type', [
                'listed', 'price_change', 'verification_completed', 'inspection_booked',
                'offer_made', 'status_change', 'disclosed_change',
            ])->index();
            $table->string('description')->nullable();
            $table->enum('privacy_level', ['public', 'aggregate_only', 'internal'])->default('public')->index();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['property_listing_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_timeline_events');
        Schema::dropIfExists('price_history');
    }
};
