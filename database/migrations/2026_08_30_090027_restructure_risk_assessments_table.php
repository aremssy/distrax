<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_assessments', function (Blueprint $table) {
            $table->enum('risk_area', [
                'title', 'ownership', 'legal', 'occupancy', 'physical_condition',
                'planning', 'liquidity', 'transaction_complexity',
            ])->nullable()->after('property_listing_id');
            $table->enum('level', ['low', 'medium', 'high'])->default('low')->after('risk_area');
            $table->text('why_explanation')->nullable()->after('level');
            $table->string('evidence_ref_id')->nullable()->after('why_explanation');
            $table->dropColumn(['risk_level']);
        });
    }

    public function down(): void
    {
        Schema::table('risk_assessments', function (Blueprint $table) {
            $table->enum('risk_level', ['low', 'medium', 'high'])->index()->after('property_listing_id');
            $table->dropColumn(['risk_area', 'level', 'why_explanation', 'evidence_ref_id']);
        });
    }
};
