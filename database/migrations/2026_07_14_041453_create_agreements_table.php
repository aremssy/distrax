<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenancy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agreement_template_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('content'); // rendered snapshot at generation time
            $table->enum('status', ['draft', 'finalized'])->default('draft')->index();
            $table->timestamp('generated_at');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
