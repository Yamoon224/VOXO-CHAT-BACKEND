<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            // En clair, jamais masqué à la relecture : l'abonné en a besoin en
            // continu pour vérifier `X-Voxo-Signature` sur chaque livraison —
            // contrairement à une clé d'API, ce n'est pas un jeton d'authentification.
            $table->string('secret');
            // Liste de `WebhookEventType` (lot 6, section 2.14) : le catalogue
            // d'événements grandira avec le temps sans jamais changer ce schéma.
            $table->json('event_types');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['workspace_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
    }
};
