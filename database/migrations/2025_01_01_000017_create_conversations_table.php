<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('starter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['property_listing_id', 'starter_id', 'recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
