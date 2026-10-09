<?php

namespace Tests\Unit\Billing;

use App\Domains\Billing\Enums\SubscriptionStatus;
use App\Domains\Billing\Services\SubscriptionService;
use App\Domains\Payments\Gateways\ArrayPaymentGateway;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\Fakes\InMemoryAiCreditLedger;
use Tests\Support\Fakes\InMemoryInvoiceRepository;
use Tests\Support\Fakes\InMemoryPlanRepository;
use Tests\Support\Fakes\InMemorySubscriptionRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    private function scope(string $workspaceId): WorkspaceScope
    {
        return new WorkspaceScope($workspaceId, 'member-1', 'user-1', WorkspaceRole::Owner);
    }

    #[Test]
    public function demarrer_un_essai_choisit_le_palier_le_plus_genereux_et_octroie_ses_credits(): void
    {
        $free = ModelFactory::plan(['slug' => 'free', 'sort_order' => 0, 'ai_credits_per_month' => 100]);
        $business = ModelFactory::plan(['slug' => 'business', 'sort_order' => 2, 'ai_credits_per_month' => 5000]);
        $enterprise = ModelFactory::plan(['slug' => 'enterprise', 'sort_order' => 3, 'is_custom' => true, 'ai_credits_per_month' => null]);

        $plans = new InMemoryPlanRepository($free, $business, $enterprise);
        $subscriptions = new InMemorySubscriptionRepository;
        $ledger = new InMemoryAiCreditLedger;

        $service = new SubscriptionService($subscriptions, $plans, new InMemoryInvoiceRepository, $ledger, new ArrayPaymentGateway);

        $service->startTrial('workspace-1');

        $subscription = $subscriptions->findForWorkspaceOrFail('workspace-1');
        $this->assertSame($business->id, $subscription->plan_id);
        $this->assertSame(SubscriptionStatus::Trialing, $subscription->status);
        $this->assertSame(5000, $ledger->balance('workspace-1'));
    }

    #[Test]
    public function basculer_vers_le_palier_gratuit_resilie_l_abonnement_chez_le_prestataire(): void
    {
        $free = ModelFactory::plan(['slug' => 'free']);
        $business = ModelFactory::plan(['slug' => 'business']);
        $subscription = ModelFactory::subscription([
            'workspace_id' => 'workspace-1', 'plan_id' => $business->id,
            'payment_provider_subscription_id' => 'sub_123',
        ]);

        $plans = new InMemoryPlanRepository($free, $business);
        $subscriptions = new InMemorySubscriptionRepository($subscription);
        $gateway = new ArrayPaymentGateway;

        $service = new SubscriptionService($subscriptions, $plans, new InMemoryInvoiceRepository, new InMemoryAiCreditLedger, $gateway);

        $updated = $service->switchToFreePlan($this->scope('workspace-1'));

        $this->assertSame($free->id, $updated->plan_id);
        $this->assertSame(SubscriptionStatus::Active, $updated->status);
        $this->assertNull($updated->payment_provider_subscription_id);
        $this->assertSame(['sub_123'], $gateway->canceledSubscriptionIds());
    }

    #[Test]
    public function activer_depuis_le_paiement_change_de_palier_et_octroie_ses_credits(): void
    {
        $business = ModelFactory::plan(['slug' => 'business', 'ai_credits_per_month' => 5000]);
        $subscription = ModelFactory::subscription(['workspace_id' => 'workspace-1']);

        $plans = new InMemoryPlanRepository($business);
        $subscriptions = new InMemorySubscriptionRepository($subscription);
        $ledger = new InMemoryAiCreditLedger;

        $service = new SubscriptionService($subscriptions, $plans, new InMemoryInvoiceRepository, $ledger, new ArrayPaymentGateway);

        $periodEnd = Carbon::parse('2027-01-01');
        $service->activateFromCheckout('workspace-1', 'business', 'cus_123', 'sub_123', $periodEnd);

        $updated = $subscriptions->findForWorkspaceOrFail('workspace-1');
        $this->assertSame($business->id, $updated->plan_id);
        $this->assertSame(SubscriptionStatus::Active, $updated->status);
        $this->assertSame('cus_123', $updated->payment_provider_customer_id);
        $this->assertSame('sub_123', $updated->payment_provider_subscription_id);
        $this->assertTrue($periodEnd->equalTo($updated->current_period_end));
        $this->assertSame(5000, $ledger->balance('workspace-1'));
    }

    #[Test]
    public function un_statut_de_rappel_du_prestataire_met_a_jour_l_abonnement(): void
    {
        $subscription = ModelFactory::subscription(['workspace_id' => 'workspace-1', 'payment_provider_subscription_id' => 'sub_123']);
        $subscriptions = new InMemorySubscriptionRepository($subscription);

        $service = new SubscriptionService($subscriptions, new InMemoryPlanRepository, new InMemoryInvoiceRepository, new InMemoryAiCreditLedger, new ArrayPaymentGateway);

        $service->updateFromProviderStatus('sub_123', 'past_due', null);

        $this->assertSame(SubscriptionStatus::PastDue, $subscriptions->findForWorkspaceOrFail('workspace-1')->status);
    }

    #[Test]
    public function une_annulation_cote_prestataire_bascule_sur_le_palier_gratuit(): void
    {
        $free = ModelFactory::plan(['slug' => 'free']);
        $subscription = ModelFactory::subscription(['workspace_id' => 'workspace-1', 'payment_provider_subscription_id' => 'sub_123']);
        $subscriptions = new InMemorySubscriptionRepository($subscription);

        $service = new SubscriptionService($subscriptions, new InMemoryPlanRepository($free), new InMemoryInvoiceRepository, new InMemoryAiCreditLedger, new ArrayPaymentGateway);

        $service->cancelFromProvider('sub_123');

        $updated = $subscriptions->findForWorkspaceOrFail('workspace-1');
        $this->assertSame($free->id, $updated->plan_id);
        $this->assertSame(SubscriptionStatus::Canceled, $updated->status);
    }

    #[Test]
    public function un_evenement_de_facture_cree_puis_met_a_jour_la_meme_facture(): void
    {
        $subscription = ModelFactory::subscription(['workspace_id' => 'workspace-1', 'payment_provider_subscription_id' => 'sub_123']);
        $subscriptions = new InMemorySubscriptionRepository($subscription);
        $invoices = new InMemoryInvoiceRepository;

        $service = new SubscriptionService($subscriptions, new InMemoryPlanRepository, $invoices, new InMemoryAiCreditLedger, new ArrayPaymentGateway);

        $service->recordInvoiceEvent('sub_123', 'in_123', 1900, 'EUR', 'open', null, now(), null);
        $this->assertCount(1, $invoices->forWorkspace('workspace-1'));
        $this->assertSame('open', $invoices->forWorkspace('workspace-1')->first()->status->value);

        $service->recordInvoiceEvent('sub_123', 'in_123', 1900, 'EUR', 'paid', 'https://stripe.test/invoice', now(), now());
        $this->assertCount(1, $invoices->forWorkspace('workspace-1'));
        $this->assertSame('paid', $invoices->forWorkspace('workspace-1')->first()->status->value);
    }

    #[Test]
    public function reconduire_les_abonnements_echus_bascule_les_essais_expires_vers_le_gratuit(): void
    {
        $free = ModelFactory::plan(['slug' => 'free', 'ai_credits_per_month' => 100]);
        $subscription = ModelFactory::subscription([
            'workspace_id' => 'workspace-1', 'status' => SubscriptionStatus::Trialing,
            'current_period_end' => now()->subDay(),
        ]);
        $subscriptions = new InMemorySubscriptionRepository($subscription);
        $ledger = new InMemoryAiCreditLedger;

        $service = new SubscriptionService($subscriptions, new InMemoryPlanRepository($free), new InMemoryInvoiceRepository, $ledger, new ArrayPaymentGateway);

        $renewed = $service->renewDueSubscriptions();

        $this->assertSame(1, $renewed);
        $updated = $subscriptions->findForWorkspaceOrFail('workspace-1');
        $this->assertSame($free->id, $updated->plan_id);
        $this->assertSame(SubscriptionStatus::Active, $updated->status);
        $this->assertSame(100, $ledger->balance('workspace-1'));
    }

    #[Test]
    public function reconduire_un_abonnement_payant_echu_avance_sa_periode_et_recharge_ses_credits(): void
    {
        $business = ModelFactory::plan(['slug' => 'business', 'ai_credits_per_month' => 5000]);
        $subscription = ModelFactory::subscription([
            'workspace_id' => 'workspace-1', 'plan_id' => $business->id, 'status' => SubscriptionStatus::Active,
            'current_period_end' => now()->subDay(),
        ]);
        $subscription->setRelation('plan', $business);
        $subscriptions = new InMemorySubscriptionRepository($subscription);
        $ledger = new InMemoryAiCreditLedger;

        $service = new SubscriptionService($subscriptions, new InMemoryPlanRepository($business), new InMemoryInvoiceRepository, $ledger, new ArrayPaymentGateway);

        $renewed = $service->renewDueSubscriptions();

        $this->assertSame(1, $renewed);
        $this->assertTrue($subscriptions->findForWorkspaceOrFail('workspace-1')->current_period_end->isFuture());
        $this->assertSame(5000, $ledger->balance('workspace-1'));
    }

    #[Test]
    public function demarrer_un_essai_sans_palier_actif_echoue(): void
    {
        $service = new SubscriptionService(new InMemorySubscriptionRepository, new InMemoryPlanRepository, new InMemoryInvoiceRepository, new InMemoryAiCreditLedger, new ArrayPaymentGateway);

        $this->expectException(RuntimeException::class);

        $service->startTrial('workspace-1');
    }
}
