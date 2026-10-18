<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('channel');
            $table->foreignUuid('segment_id')->constrained('contact_segments')->cascadeOnDelete();
            $table->foreignUuid('template_id')->constrained('campaign_templates')->cascadeOnDelete();
            // `draft` modifiable à volonté ; `sent` fige le ciblage et le
            // contenu — les statistiques d'envoi vivent à part, dans
            // `campaign_sends`, puisqu'elles continuent d'évoluer après ce
            // passage (chaque tentative se résout de façon asynchrone).
            $table->string('status')->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
