<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negotiations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('amount')->nullable(); // null when the entry is just a message
            $table->text('message')->nullable();
            $table->enum('status', ['proposed', 'accepted', 'rejected'])->default('proposed')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negotiations');
    }
};
