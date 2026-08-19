<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['percentage', 'fixed'])->default('fixed');
            $table->unsignedBigInteger('value'); // percent or flat amount
            $table->unsignedBigInteger('max_discount')->nullable();
            $table->unsignedBigInteger('min_order')->default(0);
            $table->unsignedSmallInteger('max_uses')->nullable();
            $table->unsignedSmallInteger('used_count')->default(0);
            $table->unsignedTinyInteger('max_uses_per_user')->default(1);
            $table->string('applicable_for')->nullable(); // subscription, listing_package, etc.
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
