<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit'])->index();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('balance_after');
            $table->string('description')->nullable();
            $table->string('transactionable_type')->nullable();
            $table->unsignedBigInteger('transactionable_id')->nullable();
            $table->index(['transactionable_type', 'transactionable_id'], 'wallet_tx_transactionable_idx');
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
