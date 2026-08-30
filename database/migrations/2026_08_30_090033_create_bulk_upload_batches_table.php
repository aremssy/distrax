<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_upload_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institutional_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('original_filename')->nullable();
            $table->enum('status', ['pending', 'processing', 'partial', 'complete', 'failed'])->default('pending');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('error_report')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_upload_batches');
    }
};
