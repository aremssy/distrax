<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->enum('risk_level', ['low', 'medium', 'high'])->index();
            $table->json('factors')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('assessed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_assessments');
    }
};
