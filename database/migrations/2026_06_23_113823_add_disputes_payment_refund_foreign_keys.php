<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
            $table->foreign('refund_id')->references('id')->on('refunds')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropForeign(['refund_id']);
        });
    }
};
