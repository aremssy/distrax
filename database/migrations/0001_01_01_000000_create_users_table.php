<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->enum('verification_status', ['unverified', 'pending', 'verified', 'rejected'])->default('unverified');
            $table->string('verification_document_path')->nullable();
            $table->foreignId('verification_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('verification_note')->nullable();
            $table->timestamp('verification_reviewed_at')->nullable();
            $table->string('language', 10)->default('en');
            $table->string('currency', 10)->default('USD');
            $table->char('country_code', 2)->nullable(); // ISO 3166-1 alpha-2
            $table->string('timezone', 64)->nullable();
            $table->string('avatar')->nullable();
            $table->string('social_provider', 30)->nullable();
            $table->string('social_id')->nullable();
            $table->enum('phone_visibility', ['everyone', 'registered', 'none'])->default('everyone');
            $table->timestamp('deletion_requested_at')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('last_active_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['social_provider', 'social_id'], 'users_social_idx');
            // Verification queue + dashboard KPI.
            $table->index('verification_status', 'users_verification_status_index');
            // Login / registration / OTP lookups.
            $table->index('phone', 'users_phone_index');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
