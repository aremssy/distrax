<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activations', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_code')->unique();
            $table->string('envato_item_id')->nullable();
            $table->string('license_type')->nullable();
            $table->string('buyer_email')->nullable();
            $table->string('buyer_name')->nullable();
            $table->timestamp('verified_at');
            $table->timestamp('expires_at')->nullable();
            $table->text('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activations');
    }
};
