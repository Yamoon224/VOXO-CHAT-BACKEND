<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_sources', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('name');
            $table->string('website_url')->nullable();
            $table->string('website_sitemap_url')->nullable();
            $table->string('recrawl_frequency', 16)->default('manual');
            $table->timestamp('last_crawled_at')->nullable();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Liste des sources d'un espace (préfixe workspace_id), filtrée par type.
            $table->index(['workspace_id', 'type']);
            // Sources à ré-indexer : balayée par la tâche planifiée, tous espaces confondus.
            $table->index(['recrawl_frequency', 'last_crawled_at']);
        });

        Schema::create('knowledge_documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('source_id')->constrained('knowledge_sources')->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('title');
            // Pages web : URL d'origine, unique par source pour ré-indexer sans dupliquer.
            $table->string('origin_url')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            // Chemin sur le disque de stockage (fichiers importés uniquement).
            $table->string('disk_path')->nullable();
            // Texte extrait avant découpage ; rempli directement à la création pour les
            // entrées manuelles, par la chaîne d'ingestion pour les autres.
            $table->longText('raw_content')->nullable();
            $table->text('question')->nullable();
            $table->text('answer')->nullable();
            $table->string('status', 16)->default('pending');
            $table->text('status_message')->nullable();
            $table->unsignedInteger('chunk_count')->default(0);
            $table->timestamp('indexed_at')->nullable();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Liste des documents d'une source, et d'un espace filtré par statut.
            $table->index(['source_id', 'created_at']);
            $table->index(['workspace_id', 'status']);
            // Une page web n'est indexée qu'une fois par source : une ré-exploration
            // met à jour la ligne existante plutôt que d'en empiler une seconde.
            $table->unique(['source_id', 'origin_url']);
        });

        Schema::create('knowledge_chunks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained('knowledge_documents')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->text('content');
            $table->unsignedInteger('token_count')->default(0);
            // Vecteur d'embedding en JSON : recherche par similarité cosinus calculée
            // en PHP (décision du 8 octobre 2026, pas de pgvector pour l'instant).
            $table->json('embedding')->nullable();
            $table->string('embedding_model')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'position']);
            // Recherche sémantique : tous les passages embarqués d'un espace.
            $table->index(['workspace_id', 'embedding_model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('knowledge_documents');
        Schema::dropIfExists('knowledge_sources');
    }
};
