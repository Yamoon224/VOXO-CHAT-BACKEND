<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            // Pas d'unicité sur `conversation_id` : une conversation peut
            // enchaîner plusieurs exécutions dans le temps (une par scénario
            // déclenché), une seule étant `running` à la fois — c'est la
            // logique applicative (`AutomationExecutionService`), pas une
            // contrainte, qui le garantit, puisqu'une exécution terminée
            // reste en base (`completed`) pour l'historique.
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('scenario_id')->constrained('automation_scenarios')->cascadeOnDelete();
            // Index de la prochaine étape à exécuter dans `AutomationScenario::steps`.
            $table->unsignedInteger('current_step')->default(0);
            $table->string('status')->default('running');
            $table->timestamps();

            $table->index(['conversation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_runs');
    }
};
