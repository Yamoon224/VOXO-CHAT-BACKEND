<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_tools', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            // Nom du côté de Claude (contrainte de l'API : alphanumérique,
            // tiret, underscore) — jamais le titre affiché à l'équipe.
            $table->string('name');
            $table->text('description');
            $table->string('http_method');
            // Peut porter des `{param}` substitués (encodés) à l'appel —
            // jamais les en-têtes, qui restent fixes (voir `AgentToolCaller`).
            $table->string('url');
            $table->json('headers')->nullable();
            // Liste de `{name, type, description, required, in}` — `in` vaut
            // `path`, `query` ou `body` et décide où la valeur atterrit dans
            // la requête HTTP réelle.
            $table->json('parameters');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['workspace_id', 'name']);
            $table->index(['workspace_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_tools');
    }
};
