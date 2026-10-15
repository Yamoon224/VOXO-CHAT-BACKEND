<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routing_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            // Liste de `{field, operator, value}`, toutes requises — même
            // évaluateur simple que `ContactSegmentService`, pas un moteur de
            // requête dynamique (section 2.4 : tourniquet, origine, intention).
            $table->json('conditions');
            $table->string('action_type');
            // Un seul identifiant pour `assign_user`, plusieurs pour
            // `round_robin` (le bassin d'agents).
            $table->json('assignee_user_ids');
            // Curseur du tourniquet : index du prochain agent à affecter dans
            // `assignee_user_ids`, persisté pour survivre entre affectations.
            $table->unsignedInteger('next_round_robin_index')->default(0);
            $table->timestamps();

            $table->index(['workspace_id', 'is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routing_rules');
    }
};
