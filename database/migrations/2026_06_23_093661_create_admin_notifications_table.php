<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->index();
            $table->text('title');
            $table->text('body')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->nullableMorphs('notifiable');
            $table->timestamps();

            $table->index(['admin_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
