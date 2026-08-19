<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('developer_name')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // Money is stored as a whole-unit integer everywhere else in the schema
            // (payments.amount, property_listings.price, …). Keep price_from consistent.
            $table->unsignedBigInteger('price_from')->nullable();
            $table->string('status')->default('upcoming'); // upcoming, ongoing, completed
            $table->string('cover_image')->nullable();
            $table->date('completion_date')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
