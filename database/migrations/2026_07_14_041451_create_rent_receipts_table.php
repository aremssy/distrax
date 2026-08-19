<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rent_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenancy_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_number')->unique();
            $table->unsignedBigInteger('amount');
            $table->timestamp('issued_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_receipts');
    }
};
