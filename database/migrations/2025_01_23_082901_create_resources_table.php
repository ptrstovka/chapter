<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->string('client_file_name');
            $table->unsignedBigInteger('size');
            $table->string('mime_type');
            $table->foreignId('course_id')->constrained('courses');
            $table->timestamps();
        });
    }
};
