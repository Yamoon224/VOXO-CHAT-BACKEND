<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_scenarios', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            // Brouillon par défaut : un scénario tout juste créé ne doit
            // jamais jouer sur une vraie conversation avant d'être publié.
            $table->string('status')->default('draft');
            $table->string('trigger_type');
            // Liste de `{field, operator, value}` — même évaluateur simple
            // que `RoutingRule::conditions`, pas un moteur de requête dynamique.
            $table->json('conditions');
            // Liste ordonnée de `{type, config}`, exécutées dans l'ordre —
            // voir `AutomationActionType`.
            $table->json('steps');
            $table->timestamps();

            $table->index(['workspace_id', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_scenarios');
    }
};
