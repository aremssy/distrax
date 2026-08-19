<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->enum('channel', ['email', 'push', 'sms'])->index();
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->string('subject')->nullable();
            $table->longText('content')->nullable();
            $table->json('target_segments')->nullable();
            $table->json('sent_to_user_ids')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'cancelled'])->default('draft')->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
