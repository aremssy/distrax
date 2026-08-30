<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('agreed_amount');
            $table->char('currency_code', 3)->nullable();
            $table->enum('stage', ['initiated', 'due_diligence', 'financing', 'legal_review', 'closing', 'completed', 'cancelled'])
                ->default('initiated')->index();
            $table->timestamp('closed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['buyer_id', 'stage']);
            $table->index(['seller_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
