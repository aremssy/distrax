<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('page_feedback', function (Blueprint $table) {
            $table->id();
            $table->string('page', 60)->index();
            $table->boolean('is_helpful');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['page', 'visitor_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_feedback');
    }
};
