<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('reviewable'); // property_listing, technician, agency
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('body')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('moderation_note')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->text('owner_reply')->nullable();
            $table->timestamp('owner_replied_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['reviewer_id', 'reviewable_type', 'reviewable_id']);
            $table->index(['reviewable_type', 'reviewable_id', 'is_visible']);
            // Pending-reviews KPI: WHERE moderated_at IS NULL.
            $table->index('moderated_at', 'reviews_moderated_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
