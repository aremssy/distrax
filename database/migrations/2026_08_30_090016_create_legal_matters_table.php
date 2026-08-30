<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_matters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['escrow', 'closing', 'title_search', 'contract_review'])->index();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'blocked'])->default('pending')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_matters');
    }
};
