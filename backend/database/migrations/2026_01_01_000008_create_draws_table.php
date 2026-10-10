<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tontine_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('round');
            $table->foreignId('winner_member_id')->constrained('tontine_members')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');               // cagnotte calculée côté serveur
            $table->foreignId('drawn_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('drawn_at');
            $table->timestamps();

            // Un seul tirage par tour, et un membre ne gagne qu'une fois par cycle.
            $table->unique(['cycle_id', 'round']);
            $table->unique(['cycle_id', 'winner_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draws');
    }
};
