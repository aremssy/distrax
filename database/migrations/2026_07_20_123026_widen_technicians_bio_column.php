<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The bio column was string(255) while every validation rule allows 1000
 * characters, so a longer bio failed at the database instead of validation.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->text('bio')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->string('bio')->nullable()->change();
        });
    }
};
