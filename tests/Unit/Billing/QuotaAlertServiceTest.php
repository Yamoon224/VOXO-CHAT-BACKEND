<?php

namespace Tests\Unit\Billing;

use App\Domains\Billing\Services\QuotaAlertService;
use App\Domains\Shared\Enums\WorkspaceRole;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryAiCreditLedger;
use Tests\Support\Fakes\InMemoryKnowledgeDocumentRepository;
use Tests\Support\Fakes\InMemoryMembershipRepository;
use Tests\Support\Fakes\InMemoryQuotaAlertRepository;
use Tests\Support\Fakes\InMemorySubscriptionRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\Support\Fakes\RecordingMailer;
use Tests\TestCase;

class QuotaAlertServiceTest extends TestCase
{
    private function makeOwner(InMemoryMembershipRepository $memberships, string $workspaceId): void
    {
        $owner = ModelFactory::user(['email' => 'owner@example.test']);
        $member = $memberships->add($workspaceId, $owner->id, WorkspaceRole::Owner);
        $member->setRelation('user', $owner);
    }

    #[Test]
    public function un_usage_sous_80_pour_cent_ne_declenche_aucune_alerte(): void
    {
        $workspace = ModelFactory::workspace();
        $plan = ModelFactory::plan(['max_seats' => 10, 'max_knowledge_documents' => null, 'ai_credits_per_month' => null]);
        $subscription = ModelFactory::subscription(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);
        $subscription->setRelation('plan', $plan);
        $subscription->setRelation('workspace', $workspace);

        $memberships = new InMemoryMembershipRepository;
        $this->makeOwner($memberships, $workspace->id);
        $memberships->add($workspace->id, 'user-2', WorkspaceRole::Agent);

        $mailer = new RecordingMailer;
        $service = new QuotaAlertService(
            new InMemorySubscriptionRepository($subscription),
            new InMemoryAiCreditLedger,
            $memberships,
            new InMemoryKnowledgeDocumentRepository,
            new InMemoryQuotaAlertRepository,
            $mailer,
        );

        $this->assertSame(0, $service->checkAll());
        $this->assertSame([], $mailer->sent);
    }

    #[Test]
    public function franchir_80_pour_cent_des_places_envoie_une_alerte_une_seule_fois(): void
    {
        $workspace = ModelFactory::workspace();
        $plan = ModelFactory::plan(['max_seats' => 10, 'max_knowledge_documents' => null, 'ai_credits_per_month' => null]);
        $subscription = ModelFactory::subscription(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);
        $subscription->setRelation('plan', $plan);
        $subscription->setRelation('workspace', $workspace);

        $memberships = new InMemoryMembershipRepository;
        $this->makeOwner($memberships, $workspace->id);
        for ($i = 0; $i < 7; $i++) {
            $memberships->add($workspace->id, "user-{$i}", WorkspaceRole::Agent);
        }
        // 8 membres / 10 sièges = 80 %.

        $mailer = new RecordingMailer;
        $service = new QuotaAlertService(
            new InMemorySubscriptionRepository($subscription),
            new InMemoryAiCreditLedger,
            $memberships,
            new InMemoryKnowledgeDocumentRepository,
            new InMemoryQuotaAlertRepository,
            $mailer,
        );

        $this->assertSame(1, $service->checkAll());
        $this->assertCount(1, $mailer->sent);
        $this->assertSame('owner@example.test', $mailer->sent[0]['email']);
        $this->assertSame('80', $mailer->sent[0]['data']['threshold_percent']);

        // Un second passage dans la même période ne relance pas.
        $this->assertSame(0, $service->checkAll());
        $this->assertCount(1, $mailer->sent);
    }

    #[Test]
    public function atteindre_100_pour_cent_envoie_les_deux_paliers_au_meme_passage(): void
    {
        $workspace = ModelFactory::workspace();
        $plan = ModelFactory::plan(['max_seats' => null, 'max_knowledge_documents' => 2, 'ai_credits_per_month' => null]);
        $subscription = ModelFactory::subscription(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);
        $subscription->setRelation('plan', $plan);
        $subscription->setRelation('workspace', $workspace);

        $memberships = new InMemoryMembershipRepository;
        $this->makeOwner($memberships, $workspace->id);

        $documents = new InMemoryKnowledgeDocumentRepository;
        $documents->create(['workspace_id' => $workspace->id, 'source_id' => 'source-1', 'type' => 'file', 'title' => 'a']);
        $documents->create(['workspace_id' => $workspace->id, 'source_id' => 'source-1', 'type' => 'file', 'title' => 'b']);

        $mailer = new RecordingMailer;
        $service = new QuotaAlertService(
            new InMemorySubscriptionRepository($subscription),
            new InMemoryAiCreditLedger,
            $memberships,
            $documents,
            new InMemoryQuotaAlertRepository,
            $mailer,
        );

        $this->assertSame(2, $service->checkAll());
        $thresholds = array_column(array_column($mailer->sent, 'data'), 'threshold_percent');
        sort($thresholds);
        $this->assertSame(['80', '100'], $thresholds);
    }

    #[Test]
    public function un_palier_sans_plafond_n_alerte_jamais(): void
    {
        $workspace = ModelFactory::workspace();
        $plan = ModelFactory::plan(['max_seats' => null, 'max_knowledge_documents' => null, 'ai_credits_per_month' => null]);
        $subscription = ModelFactory::subscription(['workspace_id' => $workspace->id, 'plan_id' => $plan->id]);
        $subscription->setRelation('plan', $plan);
        $subscription->setRelation('workspace', $workspace);

        $memberships = new InMemoryMembershipRepository;
        $this->makeOwner($memberships, $workspace->id);

        $mailer = new RecordingMailer;
        $service = new QuotaAlertService(
            new InMemorySubscriptionRepository($subscription),
            new InMemoryAiCreditLedger,
            $memberships,
            new InMemoryKnowledgeDocumentRepository,
            new InMemoryQuotaAlertRepository,
            $mailer,
        );

        $this->assertSame(0, $service->checkAll());
        $this->assertSame([], $mailer->sent);
    }
}
