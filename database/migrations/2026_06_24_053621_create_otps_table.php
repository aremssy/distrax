<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('code', 10);
            $table->enum('type', ['register', 'login', 'reset']);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['phone', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
