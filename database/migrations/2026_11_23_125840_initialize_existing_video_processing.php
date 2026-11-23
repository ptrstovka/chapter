<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('videos')->whereNotNull('poster_image_file_path')->whereNotNull('duration_seconds')
            ->update(['processing_status' => 'ready']);
    }

    public function down(): void {}
};
