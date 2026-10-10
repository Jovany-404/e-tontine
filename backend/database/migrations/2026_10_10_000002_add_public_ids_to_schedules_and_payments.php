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
        Schema::table('contribution_schedules', function (Blueprint $table) {
            $table->char('public_id', 26)->nullable()->after('id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->char('public_id', 26)->nullable()->after('id');
        });

        DB::table('contribution_schedules')
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunkById(100, function ($schedules): void {
                foreach ($schedules as $schedule) {
                    DB::table('contribution_schedules')
                        ->where('id', $schedule->id)
                        ->update(['public_id' => (string) Str::ulid()]);
                }
            });

        DB::table('payments')
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunkById(100, function ($payments): void {
                foreach ($payments as $payment) {
                    DB::table('payments')
                        ->where('id', $payment->id)
                        ->update(['public_id' => (string) Str::ulid()]);
                }
            });

        Schema::table('contribution_schedules', function (Blueprint $table) {
            $table->char('public_id', 26)->nullable(false)->unique()->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->char('public_id', 26)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });

        Schema::table('contribution_schedules', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
