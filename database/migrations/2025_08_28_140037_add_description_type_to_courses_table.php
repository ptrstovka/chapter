<?php

use App\Enums\TextContentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('description_type')->after('description')->nullable();
        });

        DB::table('courses')->update(['description_type' => TextContentType::Html]);

        Schema::table('courses', function (Blueprint $table) {
            $table->string('description_type')->after('description')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('description_type');
        });
    }
};
