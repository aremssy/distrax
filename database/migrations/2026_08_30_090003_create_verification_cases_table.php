<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['in_progress', 'distrax_verified', 'disclosure_required', 'under_legal_review', 'not_verified'])
                ->default('in_progress')->index();
            $table->foreignId('assigned_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->date('expiry_review_date')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_cases');
    }
};
