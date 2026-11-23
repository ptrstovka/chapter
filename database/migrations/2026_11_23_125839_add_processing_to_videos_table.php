<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change();
            $table->string('hls_playlist_path')->nullable();
            $table->string('format')->default('original');
            $table->string('processing_status')->default('queued')->index();
            $table->uuid('processing_attempt')->nullable();
            $table->json('processing_options')->nullable();
            $table->text('processing_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['processing_status']);
            $table->dropColumn(['hls_playlist_path', 'format', 'processing_status', 'processing_attempt', 'processing_options', 'processing_error']);
        });
    }
};
