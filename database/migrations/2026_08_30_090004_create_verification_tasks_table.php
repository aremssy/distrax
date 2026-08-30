<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_case_id')->constrained()->cascadeOnDelete();
            $table->enum('layer', [
                'seller_kyc', 'document_review', 'title', 'survey', 'physical', 'ownership_authority',
                'encumbrance', 'litigation', 'planning', 'final_decision',
            ])->index();
            $table->enum('owner_role', [
                'distrax', 'legal', 'property_lawyer', 'licensed_surveyor', 'distrax_inspector', 'surveyor_planning_professional',
            ]);
            $table->enum('status', ['not_started', 'in_progress', 'passed', 'failed', 'flagged'])->default('not_started')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_tasks');
    }
};
