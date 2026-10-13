<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quota_alerts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('quota_type', 30);
            $table->unsignedTinyInteger('threshold');
            $table->timestamp('sent_at');
            $table->timestamps();

            // Déduplique les relances : une ligne par palier d'alerte franchi,
            // revalidée naturellement à la période suivante (voir
            // `QuotaAlertService`, qui compare `sent_at` à la période en cours
            // plutôt que de purger ces lignes).
            $table->unique(['workspace_id', 'quota_type', 'threshold']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quota_alerts');
    }
};
