<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Journal d'activité : qui a fait quoi, et quand. Les lignes ne sont jamais modifiées.
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60);                       // ex. : membership.approved
            $table->nullableMorphs('subject');                  // objet concerné (subject_type, subject_id)
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tontine_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
