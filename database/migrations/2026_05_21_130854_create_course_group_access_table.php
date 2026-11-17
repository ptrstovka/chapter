<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_group_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses');
            $table->foreignId('access_group_id')->constrained('access_groups')->cascadeOnDelete();
            $table->foreignId('granted_by_id')->constrained('users');
            $table->timestamps();

            $table->unique(['course_id', 'access_group_id']);
            $table->index(['access_group_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_group_access');
    }
};
