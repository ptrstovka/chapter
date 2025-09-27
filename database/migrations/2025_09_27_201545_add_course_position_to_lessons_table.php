<?php

use App\Models\Course;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->unsignedBigInteger('position_within_course')->nullable()->after('position');
        });

        Course::query()->withTrashed()->eachById(function (Course $course) {
            $course->sortLessonsWithinCourse();
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->unsignedBigInteger('position_within_course')->nullable(false)->after('position')->change();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('position_within_course');
        });
    }
};
