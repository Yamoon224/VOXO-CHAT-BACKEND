<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            // Un seul canal câblé pour l'instant (`widget`) ; les autres (`email`,
            // `contact_form`) arriveront avec leur connecteur, sans changer ce champ.
            $table->string('channel', 16)->default('widget');
            $table->string('status', 16)->default('open');
            // Identifiant anonyme minté à l'ouverture de la session de visiteur
            // (jeton signé, pas de table de sessions — voir VisitorSessionToken).
            $table->string('visitor_id', 64);
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->foreignUuid('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject')->nullable();
            $table->text('summary')->nullable();
            $table->string('sentiment', 16)->nullable();
            // L'agent IA est descendu sous le seuil de confiance : passage à un
            // humain avec le contexte complet (section 2.2 du cahier des charges).
            $table->boolean('needs_human')->default(false);
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('rating_comment')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // Boîte de réception : liste par espace filtrée par statut.
            $table->index(['workspace_id', 'status', 'last_message_at']);
            // Conversations affectées à un agent.
            $table->index(['workspace_id', 'assigned_user_id']);
            // Un même visiteur revenant retrouve sa conversation ouverte.
            $table->index(['workspace_id', 'visitor_id']);
        });

        Schema::create('messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            // Dénormalisé depuis la conversation : borne les requêtes par espace
            // sans jointure, comme `knowledge_chunks.workspace_id`.
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('sender_type', 16);
            $table->foreignUuid('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            // `internal` : note d'équipe, jamais renvoyée au visiteur.
            $table->string('visibility', 16)->default('public');
            $table->text('body');
            // Passages de la base de connaissances cités par l'agent IA (section 4.5 :
            // chaque réponse enregistre les passages utilisés).
            $table->json('citations')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_filename')->nullable();
            $table->timestamps();

            // Fil de la conversation, dans l'ordre.
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('canned_responses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->timestamps();

            $table->unique(['workspace_id', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canned_responses');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
