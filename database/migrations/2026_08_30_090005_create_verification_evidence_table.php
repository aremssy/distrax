<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_task_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // id_document, title_deed, survey_plan, site_photo, legal_opinion, ...
            $table->string('file_path');
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_evidence');
    }
};
