<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->char('currency_code', 3)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
