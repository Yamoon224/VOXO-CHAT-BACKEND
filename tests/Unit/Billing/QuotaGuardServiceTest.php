<?php

namespace Tests\Unit\Billing;

use App\Domains\Billing\Services\QuotaGuardService;
use App\Domains\Shared\Enums\WorkspaceRole;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryAiCreditLedger;
use Tests\Support\Fakes\InMemoryKnowledgeDocumentRepository;
use Tests\Support\Fakes\InMemoryMembershipRepository;
use Tests\Support\Fakes\InMemorySubscriptionRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class QuotaGuardServiceTest extends TestCase
{
    #[Test]
    public function un_espace_sans_abonnement_connu_n_est_jamais_bloque(): void
    {
        $service = new QuotaGuardService(
            new InMemorySubscriptionRepository,
            new InMemoryAiCreditLedger,
            new InMemoryMembershipRepository,
            new InMemoryKnowledgeDocumentRepository,
        );

        $this->assertTrue($service->canAddSeat('workspace-1'));
        $this->assertTrue($service->canIndexDocument('workspace-1'));
        $this->assertTrue($service->canConsumeAiCredit('workspace-1'));
    }

    #[Test]
    public function un_palier_sans_plafond_n_est_jamais_bloque(): void
    {
        $workspace = ModelFactory::workspace();
        $plan = ModelFactory::plan(['max_seats' => null, 'max_knowledge_documents' => null, 'ai_credits_per_month' => null]);
        $subscription = ModelFactory::subscription(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);
        $subscription->setRelation('plan', $plan);

        $service = new QuotaGuardService(
            new InMemorySubscriptionRepository($subscription),
            new InMemoryAiCreditLedger,
            new InMemoryMembershipRepository,
            new InMemoryKnowledgeDocumentRepository,
        );

        $this->assertTrue($service->canAddSeat($workspace->id));
        $this->assertTrue($service->canIndexDocument($workspace->id));
        $this->assertTrue($service->canConsumeAiCredit($workspace->id));
    }

    #[Test]
    public function le_plafond_de_places_du_palier_bloque_au_dela(): void
    {
        $workspace = ModelFactory::workspace();
        $plan = ModelFactory::plan(['max_seats' => 1]);
        $subscription = ModelFactory::subscription(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);
        $subscription->setRelation('plan', $plan);

        $memberships = new InMemoryMembershipRepository;
        $memberships->add($workspace->id, 'user-1', WorkspaceRole::Owner);

        $service = new QuotaGuardService(
            new InMemorySubscriptionRepository($subscription),
            new InMemoryAiCreditLedger,
            $memberships,
            new InMemoryKnowledgeDocumentRepository,
        );

        $this->assertFalse($service->canAddSeat($workspace->id));
    }

    #[Test]
    public function le_plafond_de_documents_du_palier_bloque_au_dela(): void
    {
        $workspace = ModelFactory::workspace();
        $plan = ModelFactory::plan(['max_knowledge_documents' => 1]);
        $subscription = ModelFactory::subscription(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);
        $subscription->setRelation('plan', $plan);

        $documents = new InMemoryKnowledgeDocumentRepository;
        $documents->create(['workspace_id' => $workspace->id, 'source_id' => 'source-1', 'type' => 'file', 'title' => 'x']);

        $service = new QuotaGuardService(
            new InMemorySubscriptionRepository($subscription),
            new InMemoryAiCreditLedger,
            new InMemoryMembershipRepository,
            $documents,
        );

        $this->assertFalse($service->canIndexDocument($workspace->id));
    }

    #[Test]
    public function un_solde_de_credits_ia_epuise_bloque_la_consommation(): void
    {
        $workspace = ModelFactory::workspace();
        $plan = ModelFactory::plan(['ai_credits_per_month' => 10]);
        $subscription = ModelFactory::subscription(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);
        $subscription->setRelation('plan', $plan);

        $ledger = new InMemoryAiCreditLedger;

        $service = new QuotaGuardService(
            new InMemorySubscriptionRepository($subscription),
            $ledger,
            new InMemoryMembershipRepository,
            new InMemoryKnowledgeDocumentRepository,
        );

        $this->assertFalse($service->canConsumeAiCredit($workspace->id));

        $ledger->grant($subscription, 1, 'test');

        $this->assertTrue($service->canConsumeAiCredit($workspace->id));

        $service->consumeAiCredit($workspace->id, 'Réponse de test');

        $this->assertFalse($service->canConsumeAiCredit($workspace->id));
        $this->assertSame('Réponse de test', $ledger->usages[0]['reason']);
    }
}
