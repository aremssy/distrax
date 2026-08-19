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
        Schema::table('technicians', function (Blueprint $table): void {
            $table->string('slug')->nullable()->after('user_id');
        });

        $used = [];

        DB::table('technicians')
            ->join('users', 'users.id', '=', 'technicians.user_id')
            ->orderBy('technicians.id')
            ->select('technicians.id', 'users.name')
            ->lazy()
            ->each(function (object $technician) use (&$used): void {
                $base = Str::slug($technician->name) ?: 'technician';
                $slug = $base;
                $i = 1;

                while (isset($used[$slug])) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }

                $used[$slug] = true;
                DB::table('technicians')->where('id', $technician->id)->update(['slug' => $slug]);
            });

        Schema::table('technicians', function (Blueprint $table): void {
            $table->string('slug')->nullable(false)->change();
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technicians', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
