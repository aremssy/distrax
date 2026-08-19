<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_calendar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_available')->default(true);
            $table->unsignedBigInteger('override_price')->nullable();
            $table->timestamps();

            $table->unique(['property_listing_id', 'date']);
            $table->index(['property_listing_id', 'date', 'is_available'], 'avail_cal_listing_date_available_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_calendar');
    }
};
