<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');   // pending | approved | rejected
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            // Une seule demande par personne et par tontine (une demande refusée sera rouverte).
            $table->unique(['tontine_id', 'user_id']);
            $table->index(['tontine_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_requests');
    }
};
