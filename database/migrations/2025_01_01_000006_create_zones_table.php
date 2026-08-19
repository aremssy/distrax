<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->index();
            $table->enum('type', ['country', 'state', 'city', 'area'])->index();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->json('boundary')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'type']);
            // Location picker: WHERE is_active AND type IN (...) ORDER BY name.
            $table->index(['is_active', 'type', 'name'], 'zones_active_type_name_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
