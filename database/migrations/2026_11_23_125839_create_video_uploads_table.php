<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('filename');
            $table->unsignedBigInteger('size');
            $table->foreignId('temporary_upload_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('video_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('expires_at')->index();
            $table->index(['course_id', 'status']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_uploads');
    }
};
