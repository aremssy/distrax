<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('method', ['automated', 'comparative', 'professional'])->default('automated');
            $table->unsignedBigInteger('estimated_value');
            $table->char('currency_code', 3)->nullable();
            $table->unsignedTinyInteger('confidence_score')->nullable(); // 0-100
            $table->timestamp('valued_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['property_listing_id', 'valued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valuations');
    }
};
