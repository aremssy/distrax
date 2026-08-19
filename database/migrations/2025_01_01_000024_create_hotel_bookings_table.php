<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedTinyInteger('guests')->default(1);
            $table->unsignedBigInteger('amount');
            $table->char('currency_code', 3)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'partially_refunded', 'refunded'])
                ->default('pending')->index();
            $table->text('special_requests')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('refunded_amount')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['property_listing_id', 'check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_bookings');
    }
};
