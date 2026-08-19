<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->where('key', 'primary_color')
            ->where('value', '#4F46E5')
            ->update(['value' => '#5352ED']);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'primary_color')
            ->where('value', '#5352ED')
            ->update(['value' => '#4F46E5']);
    }
};
