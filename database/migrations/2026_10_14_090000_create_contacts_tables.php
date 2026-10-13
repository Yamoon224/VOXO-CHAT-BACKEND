<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            // Attributs personnalisés (section 2.6) : schéma libre par espace,
            // jamais de colonne par attribut — ça ne serait pas multi-tenant.
            $table->json('custom_attributes')->nullable();
            $table->timestamps();

            // Un même visiteur revenant (widget, e-mail) retrouve son profil.
            // MySQL autorise plusieurs NULL dans un index unique : les contacts
            // sans adresse connue ne se bloquent pas entre eux.
            $table->unique(['workspace_id', 'email']);
            $table->index(['workspace_id', 'created_at']);
        });

        Schema::create('contact_notes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('contact_id')->constrained()->cascadeOnDelete();
            // Dénormalisé depuis le contact, même raison que `messages.workspace_id`.
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['contact_id', 'created_at']);
        });

        Schema::create('contact_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            // Ex. `conversation_started`, `conversation_resolved`, `imported`, `note_added`.
            $table->string('type', 40);
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            // Historique chronologique d'un contact (section 2.6).
            $table->index(['contact_id', 'occurred_at']);
        });

        Schema::create('contact_segments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // Liste de critères `{field, operator, value}`, évaluée par
            // `ContactSegmentService::matching()` — pas un moteur de requête
            // dynamique complet, volontairement borné à des critères simples.
            $table->json('criteria');
            $table->timestamps();

            $table->unique(['workspace_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_segments');
        Schema::dropIfExists('contact_events');
        Schema::dropIfExists('contact_notes');
        Schema::dropIfExists('contacts');
    }
};
