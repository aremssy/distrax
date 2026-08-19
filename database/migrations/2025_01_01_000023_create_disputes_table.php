<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raised_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('against_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_listing_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('disputable');
            $table->foreignId('payment_id')->nullable();
            $table->foreignId('refund_id')->nullable();
            $table->string('subject');
            $table->text('description');
            $table->enum('status', ['open', 'in_review', 'resolved', 'closed'])->default('open')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution')->nullable();
            $table->enum('outcome', ['none', 'refund_approved', 'refund_rejected', 'content_removed', 'warning_issued'])->default('none');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
