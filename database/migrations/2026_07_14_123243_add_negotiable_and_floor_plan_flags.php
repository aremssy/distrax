<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_listings', function (Blueprint $table) {
            $table->boolean('is_negotiable')->default(false)->after('advance_months');
        });

        Schema::table('property_images', function (Blueprint $table) {
            // Floor plans are uploaded like any other image but are pulled out of the
            // gallery and shown in their own section on the listing page.
            $table->boolean('is_floor_plan')->default(false)->after('is_cover');
        });
    }

    public function down(): void
    {
        Schema::table('property_listings', function (Blueprint $table) {
            $table->dropColumn('is_negotiable');
        });

        Schema::table('property_images', function (Blueprint $table) {
            $table->dropColumn('is_floor_plan');
        });
    }
};
