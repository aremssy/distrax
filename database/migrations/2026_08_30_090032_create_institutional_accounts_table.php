<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutional_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('legal_entity_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('address')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutional_accounts');
    }
};
