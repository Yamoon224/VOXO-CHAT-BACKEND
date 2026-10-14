<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            // Un ticket habille une conversation (statut, canal, visiteur déjà
            // là) plutôt que de les dupliquer — section 2.7 du cahier des
            // charges : priorité et échéance SLA sont tout ce qu'il ajoute.
            $table->foreignUuid('conversation_id')->unique()->constrained()->cascadeOnDelete();
            // Dénormalisé depuis `conversations.contact_id` à la création, pour
            // la recherche directe « les tickets de ce contact » du portail
            // (jeton signé par contact, jamais par ticket — voir `TicketPortalToken`).
            $table->foreignUuid('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('priority')->default('normal');
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
