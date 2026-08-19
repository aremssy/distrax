<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tenant_name')->nullable(); // for tenants without a platform account
            $table->string('tenant_phone')->nullable();
            $table->string('tenant_email')->nullable();
            $table->unsignedBigInteger('rent_amount');
            $table->unsignedBigInteger('deposit_amount')->default(0);
            $table->unsignedTinyInteger('due_day_of_month')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'ended', 'terminated'])->default('active')->index();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['owner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenancies');
    }
};
