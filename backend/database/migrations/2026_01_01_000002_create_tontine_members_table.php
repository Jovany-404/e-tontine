<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tontine_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // Le rôle est porté par la tontine : manager | member
            $table->string('role', 20)->default('member');
            $table->string('status', 20)->default('active');    // active | inactive
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            // Un utilisateur ne peut apparaître qu'une fois dans une tontine.
            $table->unique(['tontine_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tontine_members');
    }
};
