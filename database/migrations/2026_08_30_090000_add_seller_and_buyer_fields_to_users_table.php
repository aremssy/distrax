<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('buying_for', ['my_home', 'investment', 'fix_flip', 'development', 'land_banking', 'commercial'])->nullable()->after('phone_visibility');
            $table->boolean('is_institutional')->default(false)->after('buying_for');
            $table->decimal('rating', 3, 2)->default(0)->after('is_institutional');
            $table->unsignedInteger('response_time_avg_minutes')->nullable()->after('rating');
            $table->unsignedInteger('completed_deals_count')->default(0)->after('response_time_avg_minutes');
            // Seller identity — who is listing, distinct from buying_for (why a user is browsing).
            $table->enum('seller_type', ['individual', 'company', 'estate', 'executor_administrator', 'bank_institution', 'agent', 'developer'])
                ->nullable()->after('completed_deals_count');
            $table->string('company_name')->nullable()->after('seller_type');
            $table->string('poa_document_path')->nullable()->after('company_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['buying_for', 'is_institutional', 'rating', 'response_time_avg_minutes', 'completed_deals_count', 'seller_type', 'company_name', 'poa_document_path']);
        });
    }
};
