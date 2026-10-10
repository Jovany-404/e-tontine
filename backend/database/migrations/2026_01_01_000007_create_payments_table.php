<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paiements réellement encaissés (plusieurs possibles par échéance : paiement partiel).
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('contribution_schedules')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('method', 20);                       // cash | mobile_money | transfer | other
            $table->string('reference', 100)->nullable();
            $table->string('note', 500)->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->date('paid_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
