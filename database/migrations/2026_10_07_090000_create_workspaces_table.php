<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            // Identifiant lisible des pages publiques (`/help/{slug}`).
            $table->string('slug', 80)->unique();
            $table->string('locale', 5)->default('fr');
            $table->string('timezone', 64)->default('UTC');
            $table->timestamps();
        });

        Schema::create('workspace_members', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32);
            $table->timestamps();

            // Un compte n'a qu'une adhésion par espace. L'index sert aussi la
            // liste des membres d'un espace (préfixe `workspace_id`).
            $table->unique(['workspace_id', 'user_id']);
            // « Mes espaces de travail », lu à chaque ouverture de session.
            $table->index(['user_id', 'created_at']);
            // Recherche du propriétaire et filtre de la liste par rôle.
            $table->index(['workspace_id', 'role']);
        });

        Schema::create('workspace_invitations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 32);
            // Empreinte SHA-256 du jeton envoyé par e-mail.
            $table->string('token_hash', 64)->unique();
            $table->foreignUuid('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            // Une seule invitation par adresse et par espace : réinviter
            // renouvelle la ligne au lieu d'en empiler une seconde.
            $table->unique(['workspace_id', 'email']);
            // Liste des invitations en attente d'un espace.
            $table->index(['workspace_id', 'accepted_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_invitations');
        Schema::dropIfExists('workspace_members');
        Schema::dropIfExists('workspaces');
    }
};
