<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tontines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->decimal('montant_total', 15, 2);
            $table->decimal('montant_membre', 15, 2);
            $table->integer('nombre_membres')->default(0);
            $table->integer('nombre_cycles');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('statut')->default('en_attente');
            $table->string('type_rotation')->default('chrono');
            $table->integer('delai_paiement_jours')->default(5);
            $table->boolean('notification_active')->default(true);
            $table->decimal('montant_total_paye', 15, 2)->default(0);
            $table->decimal('montant_total_restant', 15, 2)->default(0);
            $table->integer('nombre_membres_paye')->default(0);
            $table->integer('nombre_membres_retard')->default(0);
            $table->decimal('progression', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tontines');
    }
};
