<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('category')->nullable()->index();
            $table->json('tags')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            // Blog index + related posts.
            $table->index(['status', 'published_at'], 'blogs_status_published_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
