<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_probes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->unsignedSmallInteger('check_interval_minutes')->default(5);
            // `unknown` tant qu'aucune sonde n'a encore tourné — jamais `down`
            // par défaut, qui annoncerait une panne qui n'a jamais été vérifiée.
            $table->string('status')->default('unknown');
            $table->timestamp('last_checked_at')->nullable();
            // Remis à zéro dès qu'une vérification réussit — un simple
            // compteur affiché, les incidents restent ouverts à la main par
            // l'équipe (jamais créés automatiquement depuis une sonde).
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_probes');
    }
};
