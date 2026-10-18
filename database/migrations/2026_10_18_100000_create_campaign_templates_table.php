<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('channel');
            // Sans objet pour le canal chat : une conversation n'a pas de ligne d'objet.
            $table->string('subject')->nullable();
            // `{{name}}`/`{{email}}` substitués à l'envoi (lot 6, section 2.9) — un
            // texte brut, jamais une vue Blade : un modèle est écrit par le client,
            // pas par l'équipe produit.
            $table->text('body');
            $table->timestamps();

            $table->index(['workspace_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_templates');
    }
};
