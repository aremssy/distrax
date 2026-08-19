<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('body'); // supports {{tenant_name}}, {{rent_amount}}, etc. placeholders
            $table->boolean('is_default')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['owner_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_templates');
    }
};
