<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_reveals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('count')->default(1);
            $table->string('period', 7); // YYYY-MM
            $table->timestamps();

            $table->unique(['user_id', 'property_listing_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_reveals');
    }
};
