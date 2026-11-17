<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasDuplicates = DB::table('course_access_requests')
            ->select('course_id', 'user_id')
            ->where('status', 'pending')
            ->groupBy('course_id', 'user_id')
            ->havingRaw('count(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            throw new RuntimeException('Resolve duplicate pending course access requests before adding the unique constraint.');
        }

        Schema::table('course_access_requests', function (Blueprint $table) {
            $pending = $table->unsignedTinyInteger('pending')->nullable();
            $expression = "case when status = 'pending' then 1 else null end";

            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $pending->storedAs($expression);
            } else {
                $pending->virtualAs($expression);
            }

            $table->unique(['course_id', 'user_id', 'pending'], 'course_access_requests_pending_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_access_requests', function (Blueprint $table) {
            $table->dropUnique('course_access_requests_pending_unique');
            $table->dropColumn('pending');
        });
    }
};
