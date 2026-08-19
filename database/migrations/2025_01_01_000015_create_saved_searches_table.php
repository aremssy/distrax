<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->json('criteria');
            $table->boolean('alert_on')->default(false);
            $table->timestamp('last_alerted_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            // Saved-search matching job: WHERE alert_on = 1.
            $table->index('alert_on', 'saved_searches_alert_on_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
    }
};
