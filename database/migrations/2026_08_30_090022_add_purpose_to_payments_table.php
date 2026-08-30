<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('purpose', [
                'sale', 'verification_fee', 'premium_listing', 'transaction_commission', 'inspection_fee',
                'valuation_report', 'professional_subscription', 'institutional_saas', 'escrow_fee',
            ])->default('sale')->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });
    }
};
