<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('payable'); // user_subscription, hotel_booking, technician_booking, etc.
            $table->string('gateway'); // bkash, nagad, card, wallet
            $table->unsignedBigInteger('amount');
            $table->string('currency', 10)->default('BDT');
            $table->unsignedBigInteger('gateway_fee')->default(0);
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded', 'partially_refunded'])
                ->default('pending')->index();
            $table->string('transaction_ref')->nullable()->unique();
            $table->string('gateway_ref')->nullable();
            $table->json('gateway_response')->nullable();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            // Dashboard revenue: WHERE status = 'paid' AND paid_at >= ? / GROUP BY DATE(paid_at).
            $table->index(['status', 'paid_at'], 'payments_status_paid_at_index');
            $table->index('gateway', 'payments_gateway_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
