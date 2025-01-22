<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->foreignId('video_id')->nullable()->constrained('videos');
            $table->unsignedInteger('position');
            $table->foreignId('chapter_id')->constrained('chapters');
            $table->timestamps();
        });
    }
};
