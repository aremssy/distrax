<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->enum('type', ['physical', 'virtual'])->default('physical')->after('inspector_id');
            $table->timestamp('scheduled_at')->nullable()->after('type');
            $table->string('report_url')->nullable()->after('summary');
            $table->text('issues')->nullable()->after('report_url');
            $table->timestamp('buyer_acknowledged_at')->nullable()->after('issues');
            $table->json('geodata')->nullable()->after('gps_lng');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn(['type', 'scheduled_at', 'report_url', 'issues', 'buyer_acknowledged_at', 'geodata']);
        });
    }
};
