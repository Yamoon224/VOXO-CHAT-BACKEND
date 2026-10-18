<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_incident_updates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('incident_id')->constrained('status_incidents')->cascadeOnDelete();
            // Le statut de l'incident au moment de cette mise à jour : la
            // chronologie publique affiche « investigating » puis
            // « identified » etc., chacun avec son propre message.
            $table->string('status');
            $table->text('body');
            $table->timestamps();

            $table->index(['incident_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_incident_updates');
    }
};
