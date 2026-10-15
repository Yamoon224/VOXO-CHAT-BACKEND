<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbox_views', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            // `{status?, assigned_user_id?, channel?, needs_human?}` — exactement
            // les filtres déjà acceptés par `ConversationRepositoryContract::paginate()`,
            // juste nommés et enregistrés (section 2.4 : sous-boîtes).
            $table->json('filters');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_views');
    }
};
