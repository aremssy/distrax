<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds `draft` to the listing status enum so the Add Property wizard can save
     * unfinished work. Drafts are owner-only: they never appear publicly and never
     * count against the post limit.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `property_listings` MODIFY `status` ENUM('draft', 'pending', 'active', 'rented', 'sold', 'archived', 'rejected') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::table('property_listings')->where('status', 'draft')->update(['status' => 'pending']);

        DB::statement("ALTER TABLE `property_listings` MODIFY `status` ENUM('pending', 'active', 'rented', 'sold', 'archived', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
