<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparable_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('valuation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_listing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_reference')->nullable(); // address/source-id for comps outside the platform
            $table->string('address')->nullable();
            $table->unsignedBigInteger('sale_price')->nullable();
            $table->date('sale_date')->nullable();
            $table->decimal('distance_km', 6, 2)->nullable();
            $table->unsignedTinyInteger('similarity_score')->nullable(); // 0-100
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparable_properties');
    }
};
