<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ask_distrax_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index(); // groups anonymous/guest queries
            $table->text('query');
            $table->text('response')->nullable();
            $table->json('context')->nullable(); // e.g. property_listing_id, model used
            $table->unsignedInteger('latency_ms')->nullable();
            $table->boolean('was_helpful')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ask_distrax_queries');
    }
};
