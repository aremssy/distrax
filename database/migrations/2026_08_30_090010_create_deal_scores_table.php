<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score'); // 0-100
            $table->json('breakdown')->nullable(); // e.g. price_vs_market, days_on_market, ...
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->index(['property_listing_id', 'computed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_scores');
    }
};
