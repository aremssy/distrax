<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->string('reference_id')->unique(); // DTX-VER-{6 digit}
            $table->decimal('score', 5, 2); // 0-100
            $table->string('seller_verification_status')->nullable();
            $table->string('title_status')->nullable();
            $table->string('ownership_status')->nullable();
            $table->string('survey_status')->nullable();
            $table->string('physical_inspection_status')->nullable();
            $table->string('legal_review_status')->nullable();
            $table->string('planning_status')->nullable();
            $table->unsignedInteger('disclosure_count')->default(0);
            $table->json('breakdown')->nullable();
            $table->timestamp('verification_date');
            $table->date('expiry_review_date')->nullable();
            // The verify URL the QR encodes (/verify/{reference_id}) — image render is a frontend/package concern, not stored here.
            $table->string('qr_code_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_scores');
    }
};
