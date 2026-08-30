<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_listings', function (Blueprint $table) {
            $table->enum('distress_reason_category', [
                'divorce', 'relocation', 'debt', 'estate_probate', 'bank_repossession', 'urgent_cash_need', 'other',
            ])->nullable()->after('status');
            $table->enum('distress_reason_visibility', ['public', 'disclosure_only', 'private'])
                ->default('disclosure_only')->after('distress_reason_category');
            $table->enum('expected_closing_period', ['flexible', '30_days', '60_days', '90_days', 'immediate'])
                ->nullable()->after('distress_reason_visibility');
            $table->enum('negotiation_flexibility', ['firm', 'negotiable', 'highly_negotiable', 'make_an_offer'])
                ->nullable()->after('expected_closing_period');
            // Seller-declared estimate, distinct from Distrax's own valuations records.
            $table->decimal('expected_market_value', 15, 2)->nullable()->after('negotiation_flexibility');
            // Denormalized from deal_scores.score so existing search/sort can order by it without a join.
            $table->decimal('deal_score_cached', 5, 2)->nullable()->after('expected_market_value');
            $table->foreignId('verification_case_id')->nullable()->after('deal_score_cached')
                ->constrained('verification_cases')->nullOnDelete();
        });

        Schema::table('property_listings', function (Blueprint $table) {
            $table->index(['status', 'deal_score_cached']);
        });
    }

    public function down(): void
    {
        Schema::table('property_listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verification_case_id');
            $table->dropIndex(['status', 'deal_score_cached']);
            $table->dropColumn([
                'distress_reason_category', 'distress_reason_visibility', 'expected_closing_period',
                'negotiation_flexibility', 'expected_market_value', 'deal_score_cached',
            ]);
        });
    }
};
