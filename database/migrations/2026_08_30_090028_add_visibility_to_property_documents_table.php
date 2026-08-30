<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_documents', function (Blueprint $table) {
            $table->enum('visibility_level', [
                'public', 'verified_buyer', 'nda_stage', 'internal_only',
            ])->default('internal_only')->index()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('property_documents', function (Blueprint $table) {
            $table->dropColumn(['visibility_level']);
        });
    }
};
