<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenancy_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_date');
            $table->unsignedBigInteger('amount');
            $table->char('currency_code', 3)->nullable();
            $table->enum('status', ['pending', 'paid', 'overdue', 'waived'])->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenancy_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_payments');
    }
};
