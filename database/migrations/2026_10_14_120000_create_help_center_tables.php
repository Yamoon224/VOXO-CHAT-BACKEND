<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_center_categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // Dérivé du nom (`HelpCenterSlug`), jamais saisi : l'URL publique
            // d'un article en dépend (section 2.10, référencement).
            $table->string('slug');
            $table->string('locale', 10)->default('fr');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['workspace_id', 'locale', 'slug']);
        });

        Schema::create('help_center_articles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('help_center_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('locale', 10)->default('fr');
            $table->longText('body');
            // `draft` : jamais servi par les pages publiques — c'est tout ce
            // que « articles privés » (section 2.10) signifie ici, pas un
            // niveau d'accès distinct par lien.
            $table->string('status')->default('draft');
            $table->string('meta_description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'locale', 'slug']);
            $table->index(['workspace_id', 'locale', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_center_articles');
        Schema::dropIfExists('help_center_categories');
    }
};
