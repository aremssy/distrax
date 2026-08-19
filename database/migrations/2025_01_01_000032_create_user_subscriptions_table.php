<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])->default('pending')->index();
            $table->unsignedSmallInteger('posts_used')->default(0);
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->unsignedBigInteger('refunded_amount')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
