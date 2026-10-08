<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('primary_color', 7)->default('#4F46E5');
            $table->string('logo_url')->nullable();
            $table->string('position', 16)->default('bottom_right');
            $table->text('welcome_message')->nullable();
            $table->string('language', 5)->default('fr');
            // Un réglage par jour de la semaine (0 = dimanche), ou absent : ouvert en continu.
            $table->json('business_hours')->nullable();
            $table->text('offline_message')->nullable();
            $table->timestamps();

            $table->unique('workspace_id');
        });

        Schema::create('assistant_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            // Ton, personnalité, consignes (section 2.2) : injecté dans le prompt système.
            $table->text('tone_instructions')->nullable();
            // Sous ce seuil [0, 1], l'agent escalade plutôt que répondre à l'aveugle.
            $table->decimal('confidence_threshold', 3, 2)->default(0.60);
            $table->timestamps();

            $table->unique('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_settings');
        Schema::dropIfExists('widget_settings');
    }
};
