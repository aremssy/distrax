<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disclosures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('disclosed_by')->constrained('users')->cascadeOnDelete();
            $table->string('category'); // structural, legal, environmental, ...
            $table->text('description');
            $table->string('document_path')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disclosures');
    }
};
