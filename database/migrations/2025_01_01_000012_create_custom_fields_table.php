<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('property_type')->index(); // rent, sale, hotel, mess, land, all
            $table->string('label');
            $table->string('key')->index();
            $table->enum('type', ['text', 'number', 'select', 'multiselect', 'checkbox', 'radio', 'date', 'textarea']);
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
