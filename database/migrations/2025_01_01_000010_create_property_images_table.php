<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('disk')->default('local');
            $table->boolean('is_cover')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('property_listing_id');
            // Cover-image lookup on every listing card.
            $table->index(['property_listing_id', 'is_cover'], 'property_images_listing_is_cover_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_images');
    }
};
