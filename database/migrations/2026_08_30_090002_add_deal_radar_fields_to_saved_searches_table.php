<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_searches', function (Blueprint $table) {
            // A saved search with is_mandate=true is a Deal Radar rule — same table, no separate deal_radar_rules table.
            $table->boolean('is_mandate')->default(false)->after('criteria')->index();
            $table->unsignedTinyInteger('min_discount_pct')->nullable()->after('is_mandate');
            $table->unsignedTinyInteger('min_deal_score')->nullable()->after('min_discount_pct');
            $table->enum('frequency', ['instant', 'daily_digest', 'weekly_digest'])->default('instant')->after('min_deal_score');
        });
    }

    public function down(): void
    {
        Schema::table('saved_searches', function (Blueprint $table) {
            $table->dropColumn(['is_mandate', 'min_discount_pct', 'min_deal_score', 'frequency']);
        });
    }
};
