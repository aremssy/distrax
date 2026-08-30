<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->enum('stage', [
                'offer_accepted', 'inspection', 'legal_review', 'closing',
                'completed', 'fell_through',
            ])->default('offer_accepted')->change();
        });

        Schema::table('legal_matters', function (Blueprint $table) {
            $table->enum('status', [
                'pending', 'in_review', 'cleared', 'issue_found',
            ])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->enum('stage', ['initiated', 'due_diligence', 'financing', 'legal_review', 'closing', 'completed', 'cancelled'])
                ->default('initiated')->change();
        });

        Schema::table('legal_matters', function (Blueprint $table) {
            $table->enum('status', ['pending', 'in_progress', 'completed', 'blocked'])->default('pending')->change();
        });
    }
};
