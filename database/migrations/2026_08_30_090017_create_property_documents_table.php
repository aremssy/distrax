<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable'); // PropertyListing, Deal, Tenancy, ...
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // title_deed, floor_plan, tax_receipt, disclosure, ...
            $table->string('file_path');
            $table->boolean('is_verified')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_documents');
    }
};
