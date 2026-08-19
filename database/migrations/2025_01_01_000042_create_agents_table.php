<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['agency_id', 'user_id']);
            // Homepage + agents listing.
            $table->index('is_active', 'agents_is_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
