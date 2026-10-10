<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Échéances : ce que chaque membre doit payer, à chaque tour d'un cycle.
        Schema::create('contribution_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('round');              // numéro du tour dans le cycle
            $table->foreignId('tontine_member_id')->constrained('tontine_members')->cascadeOnDelete();
            $table->date('due_date');
            $table->unsignedBigInteger('amount_expected');
            $table->unsignedBigInteger('amount_paid')->default(0);
            $table->string('status', 20)->default('pending');   // pending | partial | paid
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Une seule échéance par membre et par tour.
            $table->unique(['cycle_id', 'round', 'tontine_member_id'], 'schedule_unique_member_round');
            // Sert à repérer rapidement les retards.
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_schedules');
    }
};
