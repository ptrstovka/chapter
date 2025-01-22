<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->foreignId('author_id')->constrained('authors');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('trailer_id')->nullable()->constrained('videos');
            $table->string('cover_image_file_path')->nullable();
            $table->foreignId('category_id')->constrained('categories');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
