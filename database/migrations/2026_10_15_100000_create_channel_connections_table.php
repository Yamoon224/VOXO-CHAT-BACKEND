<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_connections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            // `ConversationChannel` : whatsapp, messenger, instagram, telegram, sms
            // (lot 5, section 2.5) — contrairement à l'e-mail (transport SMTP déjà
            // configuré pour tout l'applicatif, section 2.15), ces canaux n'ont pas
            // de transport partagé : chaque espace connecte son propre numéro/bot/page.
            $table->string('channel');
            $table->boolean('enabled')->default(false);
            // Jeton d'accès, identifiant de numéro/bot, jeton de vérification du
            // webhook (Meta)... la forme varie par canal, voir les pilotes concrets.
            $table->json('credentials')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_connections');
    }
};
