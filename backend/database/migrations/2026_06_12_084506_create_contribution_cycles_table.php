<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contribution_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->constrained('tontines')->onDelete('cascade');
            $table->integer('cycle_number');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->decimal('montant_total_prevu', 15, 2);
            $table->decimal('montant_total_paye', 15, 2)->default(0);
            $table->string('statut')->default('prevu');
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_cycles');
    }
};
