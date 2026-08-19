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
        Schema::table('payments', function (Blueprint $table) {
            // Set when a ListingPackage purchase is a "boost" applied to one of the
            // owner's existing listings, so payment activation knows which listing
            // to feature once the payment clears.
            $table->foreignId('property_listing_id')->nullable()->after('payable_id')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('property_listing_id');
        });
    }
};
