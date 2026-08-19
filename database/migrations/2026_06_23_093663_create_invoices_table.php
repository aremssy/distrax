<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('invoiceable');
            $table->string('type')->index();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 10)->default('BDT');
            $table->string('status')->default('paid')->index();
            $table->text('notes')->nullable();
            $table->timestamp('issued_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
