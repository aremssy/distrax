<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->timestamp('scheduled_at');
            $table->unsignedBigInteger('agreed_amount')->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->boolean('is_urgent')->default(false)->index();
            $table->enum('status', ['pending', 'quoted', 'accepted', 'in_progress', 'completed', 'cancelled'])
                ->default('pending')->index();
            $table->text('address');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_bookings');
    }
};
