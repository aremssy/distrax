<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('property_listings', function (Blueprint $table): void {
            $table->string('slug')->nullable()->after('title');
        });

        $used = [];

        DB::table('property_listings')->orderBy('id')->select('id', 'title')->lazy()->each(function (object $listing) use (&$used): void {
            $base = Str::slug($listing->title) ?: 'listing';
            $slug = $base;
            $i = 1;

            while (isset($used[$slug])) {
                $slug = "{$base}-{$i}";
                $i++;
            }

            $used[$slug] = true;
            DB::table('property_listings')->where('id', $listing->id)->update(['slug' => $slug]);
        });

        Schema::table('property_listings', function (Blueprint $table): void {
            $table->string('slug')->nullable(false)->change();
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_listings', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
