<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug', 40)->unique();
            $table->string('name');
            // `null` : tarif sur devis (offre grands comptes), pas un palier gratuit.
            $table->unsignedInteger('price_cents')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->string('billing_interval', 16)->default('month');
            // `null` sur l'un de ces plafonds : illimité (offre grands comptes).
            $table->unsignedInteger('max_seats')->nullable();
            $table->unsignedInteger('max_contacts')->nullable();
            $table->unsignedInteger('ai_credits_per_month')->nullable();
            $table->unsignedInteger('max_knowledge_documents')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            // Identifiant du tarif chez le prestataire de paiement (section Stripe du
            // tableau de bord) : absent pour le palier gratuit et l'offre sur devis.
            $table->string('provider_price_id')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('plan_id')->constrained();
            $table->string('status', 16)->default('trialing');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('payment_provider', 16)->nullable();
            $table->string('payment_provider_customer_id')->nullable();
            $table->string('payment_provider_subscription_id')->nullable();
            $table->timestamps();

            // Un espace de travail n'a qu'un abonnement.
            $table->unique('workspace_id');
            // Sonde planifiée : abonnements dont la période est échue.
            $table->index(['status', 'current_period_end']);
            // Retrouver l'abonnement désigné par un évènement du prestataire.
            $table->index('payment_provider_subscription_id');
        });

        Schema::create('ai_credit_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            // Signé : positif pour une dotation ou une recharge, négatif pour une
            // consommation. Le solde est la somme de ces lignes — jamais un
            // compteur à part, qui pourrait diverger du détail qui l'explique.
            $table->integer('amount');
            $table->string('reason')->nullable();
            $table->string('reference_id')->nullable();
            $table->timestamps();

            // Solde courant d'un espace : somme des lignes, les plus récentes en premier.
            $table->index(['workspace_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_credit_transactions');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
