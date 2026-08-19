<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('reward_type');
            $table->unsignedInteger('reward_value');
            $table->string('reward_description')->nullable();
            $table->unsignedSmallInteger('max_referrals')->nullable();
            $table->unsignedInteger('min_purchase_amount')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_rules');
    }
};
