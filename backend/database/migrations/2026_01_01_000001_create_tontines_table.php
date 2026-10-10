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
            $table->char('public_id', 26)->unique();
            // Le gérant ne peut pas être supprimé tant qu'il gère une tontine.
            $table->foreignId('manager_id')->constrained('users')->restrictOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('type', 20)->default('other');       // family | friends | work | other
            $table->unsignedBigInteger('contribution_amount');  // en FCFA, sans décimales
            $table->string('frequency', 20);                    // daily | weekly | monthly
            $table->date('start_date');
            $table->unsignedSmallInteger('expected_members');
            $table->text('rules')->nullable();
            $table->string('status', 20)->default('draft');     // draft | active | completed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tontines');
    }
};
