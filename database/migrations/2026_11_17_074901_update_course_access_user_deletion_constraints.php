<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['course_enrollments', 'completed_lessons', 'favorite_courses'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        Schema::table('temporary_uploads', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('invitations', function (Blueprint $table) {
            $table->dropForeign(['used_by_id']);
            $table->foreign('used_by_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('course_user_access', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['granted_by_id']);
            $table->foreignId('granted_by_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('granted_by_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('course_group_access', function (Blueprint $table) {
            $table->dropForeign(['granted_by_id']);
            $table->foreignId('granted_by_id')->nullable()->change();
            $table->foreign('granted_by_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('course_access_requests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['resolved_by_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('resolved_by_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['course_enrollments', 'completed_lessons', 'favorite_courses', 'temporary_uploads'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->foreign('user_id')->references('id')->on('users');
            });
        }

        Schema::table('invitations', function (Blueprint $table) {
            $table->dropForeign(['used_by_id']);
            $table->foreign('used_by_id')->references('id')->on('users');
        });

        Schema::table('course_user_access', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['granted_by_id']);
            $table->foreignId('granted_by_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('granted_by_id')->references('id')->on('users');
        });

        Schema::table('course_group_access', function (Blueprint $table) {
            $table->dropForeign(['granted_by_id']);
            $table->foreignId('granted_by_id')->nullable(false)->change();
            $table->foreign('granted_by_id')->references('id')->on('users');
        });

        Schema::table('course_access_requests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['resolved_by_id']);
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('resolved_by_id')->references('id')->on('users');
        });
    }
};
