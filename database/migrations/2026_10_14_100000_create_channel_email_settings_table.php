<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_email_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            // Désactivé par défaut : un espace qui n'a pas renseigné d'adresse
            // d'envoi ne doit pas tenter d'expédier des réponses.
            $table->boolean('enabled')->default(false);
            // « Domaine d'envoi personnalisé » (section 2.5) : en pratique une
            // adresse/un nom d'expéditeur par espace, pas une vérification DNS
            // (SPF/DKIM), hors périmètre logiciel de ce lot.
            $table->string('reply_from_email')->nullable();
            $table->string('reply_from_name')->nullable();
            $table->timestamps();

            $table->unique('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_email_settings');
    }
};
