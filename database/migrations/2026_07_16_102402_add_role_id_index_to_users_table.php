<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * users.role_id is declared as a raw unsignedBigInteger (not constrained()),
     * so it never received an automatic foreign-key index. Role-based filters
     * and joins were full-scanning the table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('role_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role_id']);
        });
    }
};
