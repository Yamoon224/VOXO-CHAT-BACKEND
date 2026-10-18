<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_probe_checks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('probe_id')->constrained('status_probes')->cascadeOnDelete();
            $table->string('status');
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['probe_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_probe_checks');
    }
};
