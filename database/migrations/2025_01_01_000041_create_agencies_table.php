<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->boolean('is_verified')->default(false);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('property_listings', function (Blueprint $table) {
            $table->foreign('agency_id')->references('id')->on('agencies')->nullOnDelete();
            $table->index('agency_id');
        });
    }

    public function down(): void
    {
        Schema::table('property_listings', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropIndex(['agency_id']);
        });

        Schema::dropIfExists('agencies');
    }
};
