<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->foreignId('booked_by')->nullable()->after('property_listing_id')
                ->constrained('users')->nullOnDelete()->index();
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booked_by');
        });
    }
};
