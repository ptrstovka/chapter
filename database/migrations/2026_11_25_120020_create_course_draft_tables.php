<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('has_been_published')->default(false);
            $table->unsignedBigInteger('studio_revision')->default(0);
        });

        DB::table('courses')->whereIn('status', ['published', 'unpublished'])->update(['has_been_published' => true]);

        Schema::create('course_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('description_type');
            $table->foreignId('category_id')->nullable()->constrained();
            $table->string('cover_image_file_path')->nullable();
            $table->foreignId('trailer_id')->nullable()->constrained('videos');
            $table->boolean('has_changes')->default(false);
            $table->timestamps();
        });

        Schema::create('course_draft_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('course_drafts')->cascadeOnDelete();
            $table->uuid()->unique();
            $table->string('title')->nullable();
            $table->unsignedInteger('position');
            $table->timestamps();
        });

        Schema::create('course_draft_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('course_drafts')->cascadeOnDelete();
            $table->foreignId('chapter_id')->constrained('course_draft_chapters')->cascadeOnDelete();
            $table->uuid()->unique();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->longText('description')->nullable();
            $table->string('description_type');
            $table->foreignId('video_id')->nullable()->constrained('videos');
            $table->unsignedInteger('position');
            $table->unsignedInteger('position_within_course')->default(0);
            $table->timestamps();
        });

        Schema::create('course_draft_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('course_drafts')->cascadeOnDelete();
            $table->uuid()->unique();
            $table->string('file_path');
            $table->string('client_file_name');
            $table->unsignedBigInteger('size');
            $table->string('mime_type');
            $table->timestamps();
        });

        Schema::create('course_draft_lesson_resource', function (Blueprint $table) {
            $table->foreignId('lesson_id')->constrained('course_draft_lessons')->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('course_draft_resources')->cascadeOnDelete();
            $table->primary(['lesson_id', 'resource_id']);
        });

        Schema::table('video_uploads', function (Blueprint $table) {
            $table->foreignId('course_draft_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('draft_lesson_id')->nullable()->constrained('course_draft_lessons')->nullOnDelete();
            $table->uuid('lesson_uuid')->nullable();
            $table->unsignedBigInteger('editor_revision')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('video_uploads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('draft_lesson_id');
            $table->dropConstrainedForeignId('course_draft_id');
            $table->dropColumn(['editor_revision', 'lesson_uuid']);
        });

        Schema::dropIfExists('course_draft_lesson_resource');
        Schema::dropIfExists('course_draft_resources');
        Schema::dropIfExists('course_draft_lessons');
        Schema::dropIfExists('course_draft_chapters');
        Schema::dropIfExists('course_drafts');

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['has_been_published', 'studio_revision']);
        });
    }
};
