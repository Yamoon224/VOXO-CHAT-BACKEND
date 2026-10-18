<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_maintenances', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamp('scheduled_start');
            $table->timestamp('scheduled_end');
            $table->timestamps();

            $table->index(['workspace_id', 'scheduled_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_maintenances');
    }
};
