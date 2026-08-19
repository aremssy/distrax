<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uploaded video files keep their storage location here so they can be
     * served and deleted; external videos (YouTube/Vimeo) leave both null.
     */
    public function up(): void
    {
        Schema::table('property_videos', function (Blueprint $table) {
            $table->string('path')->nullable()->after('url');
            $table->string('disk', 50)->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('property_videos', function (Blueprint $table) {
            $table->dropColumn(['path', 'disk']);
        });
    }
};
