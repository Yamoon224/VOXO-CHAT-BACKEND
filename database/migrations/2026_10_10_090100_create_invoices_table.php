<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('payment_provider_invoice_id')->nullable();
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('EUR');
            $table->string('status', 16);
            // Page Stripe hébergée : pas de génération de PDF maison pour l'instant.
            $table->string('hosted_invoice_url')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique('payment_provider_invoice_id');
            $table->index(['workspace_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
