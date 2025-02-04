<?php

use App\Models\Resource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->uuid()->after('id')->nullable();
        });

        Resource::query()->eachById(fn (Resource $resource) => $resource->update([
            'uuid' => Str::uuid()->toString(),
        ]));

        Schema::table('resources', function (Blueprint $table) {
            $table->uuid()->after('id')->nullable(false)->change();
        });
    }
};
