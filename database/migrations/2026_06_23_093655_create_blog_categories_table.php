<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->foreignId('blog_category_id')->nullable()->constrained('blog_categories')->nullOnDelete()->after('author_id');
            $table->dropColumn('category');

            // Related posts / category filter: WHERE blog_category_id = ? AND status = ? ORDER BY published_at DESC.
            $table->index(['blog_category_id', 'status', 'published_at'], 'blogs_category_status_published_index');
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('category')->nullable()->after('author_id');
            $table->dropForeign(['blog_category_id']);
            $table->dropColumn('blog_category_id');
        });

        Schema::dropIfExists('blog_categories');
    }
};
