<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_listing_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenancy_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['income', 'expense'])->index();
            $table->string('category');
            $table->unsignedBigInteger('amount');
            $table->date('occurred_on');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'type', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_transactions');
    }
};
