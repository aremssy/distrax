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
        Schema::table('agents', function (Blueprint $table): void {
            $table->string('slug')->nullable()->after('user_id');
        });

        $used = [];

        DB::table('agents')
            ->join('users', 'users.id', '=', 'agents.user_id')
            ->orderBy('agents.id')
            ->select('agents.id', 'users.name')
            ->lazy()
            ->each(function (object $agent) use (&$used): void {
                $base = Str::slug($agent->name) ?: 'agent';
                $slug = $base;
                $i = 1;

                while (isset($used[$slug])) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }

                $used[$slug] = true;
                DB::table('agents')->where('id', $agent->id)->update(['slug' => $slug]);
            });

        Schema::table('agents', function (Blueprint $table): void {
            $table->string('slug')->nullable(false)->change();
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
