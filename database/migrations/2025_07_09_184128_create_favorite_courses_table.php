<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_courses', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained('courses');
            $table->foreignId('user_id')->constrained('users');
            $table->primary(['course_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_courses');
    }
};
