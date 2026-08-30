<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->boolean('is_watchlist')->default(false)->after('property_listing_id');
            $table->unsignedBigInteger('target_price')->nullable()->after('is_watchlist');
            $table->unsignedBigInteger('alert_threshold')->nullable()->after('target_price');
            $table->text('notes')->nullable()->after('alert_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropColumn(['is_watchlist', 'target_price', 'alert_threshold', 'notes']);
        });
    }
};
