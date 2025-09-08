<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('single_sign_on_providers', function (Blueprint $table) {
            $table->longText('configuration')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('single_sign_on_providers', function (Blueprint $table) {
            $table->json('configuration')->nullable()->change();
        });
    }
};
