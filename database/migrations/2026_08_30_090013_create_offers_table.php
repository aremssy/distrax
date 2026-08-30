<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->char('currency_code', 3)->nullable();
            $table->text('terms')->nullable();
            $table->enum('status', ['pending', 'countered', 'accepted', 'rejected', 'withdrawn', 'expired'])
                ->default('pending')->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['property_listing_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
