<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('draws', function (Blueprint $table) {
            $table->char('public_id', 26)->nullable()->after('id');
        });

        DB::table('draws')
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunkById(100, function ($draws): void {
                foreach ($draws as $draw) {
                    DB::table('draws')
                        ->where('id', $draw->id)
                        ->update(['public_id' => (string) Str::ulid()]);
                }
            });

        Schema::table('draws', function (Blueprint $table) {
            $table->char('public_id', 26)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('draws', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
