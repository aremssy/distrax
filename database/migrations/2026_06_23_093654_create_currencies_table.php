<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->string('symbol', 10);
            $table->enum('symbol_position', ['before', 'after'])->default('before');
            $table->tinyInteger('decimal_places')->unsigned()->default(2);
            $table->string('thousands_separator', 5)->default(',');
            $table->string('decimal_separator', 5)->default('.');
            $table->decimal('exchange_rate', 15, 6)->default(1.000000);
            $table->boolean('rate_locked')->default(false); // admin-pinned rate skips the live refresh
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
