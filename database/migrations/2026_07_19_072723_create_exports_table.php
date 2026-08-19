<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resource');           // export profile key, e.g. "users"
            $table->string('format', 10);         // csv | xlsx | pdf
            $table->string('scope', 20);          // selected | page | filtered | all
            $table->json('columns');              // chosen column keys
            $table->json('filters')->nullable();  // captured request filters
            $table->json('ids')->nullable();      // selected-row ids (scope = selected)
            $table->string('status', 20)->default('queued'); // queued|processing|completed|failed
            $table->unsignedInteger('total_rows')->nullable();
            $table->unsignedInteger('processed_rows')->default(0);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
