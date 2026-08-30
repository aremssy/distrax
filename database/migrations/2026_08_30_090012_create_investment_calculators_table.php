<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_calculators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_listing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->json('inputs'); // purchase_price, down_payment, interest_rate, loan_term, rent_estimate, expenses, ...
            $table->json('results')->nullable(); // cached: cap_rate, cash_on_cash, roi, monthly_cash_flow, ...
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_calculators');
    }
};
