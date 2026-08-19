<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();
            $table->string('headline');
            $table->text('subheadline')->nullable();
            $table->longText('content')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique('zone_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_landing_pages');
    }
};
