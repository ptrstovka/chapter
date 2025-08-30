<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid()->index();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('disk');
            $table->string('path');
            $table->string('scope');
            $table->string('mime_type');
            $table->string('client_file_name');
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_uploads');
    }
};
