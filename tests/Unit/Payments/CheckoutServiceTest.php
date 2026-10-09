<?php

namespace Tests\Unit\Payments;

use App\Domains\Billing\Exceptions\PlanRequiresSalesContactException;
use App\Domains\Payments\Gateways\ArrayPaymentGateway;
use App\Domains\Payments\Services\CheckoutService;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\Fakes\InMemoryPlanRepository;
use Tests\Support\Fakes\InMemorySubscriptionRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    private function scope(string $workspaceId): WorkspaceScope
    {
        return new WorkspaceScope($workspaceId, 'member-1', 'user-1', WorkspaceRole::Owner);
    }

    #[Test]
    public function demarrer_le_paiement_cree_un_client_puis_ouvre_une_session(): void
    {
        $plan = ModelFactory::plan(['slug' => 'business', 'provider_price_id' => 'price_123']);
        $subscription = ModelFactory::subscription(['workspace_id' => 'workspace-1']);

        $plans = new InMemoryPlanRepository($plan);
        $subscriptions = new InMemorySubscriptionRepository($subscription);
        $gateway = new ArrayPaymentGateway;

        $service = new CheckoutService($gateway, $plans, $subscriptions, 'https://app.voxo.test');

        $url = $service->startCheckout($this->scope('workspace-1'), 'business', 'awa@example.test', 'Awa Koné');

        $this->assertStringStartsWith('https://checkout.stripe.test/', $url);
        $this->assertCount(1, $gateway->checkoutSessions());
        $this->assertSame('price_123', $gateway->checkoutSessions()[0]['provider_price_id']);
        $this->assertSame('workspace-1', $gateway->checkoutSessions()[0]['metadata']['workspace_id']);
        $this->assertNotNull($subscriptions->findForWorkspaceOrFail('workspace-1')->payment_provider_customer_id);
    }

    #[Test]
    public function un_client_deja_connu_n_est_pas_recree(): void
    {
        $plan = ModelFactory::plan(['slug' => 'business', 'provider_price_id' => 'price_123']);
        $subscription = ModelFactory::subscription(['workspace_id' => 'workspace-1', 'payment_provider_customer_id' => 'cus_existing']);

        $gateway = new ArrayPaymentGateway;
        $service = new CheckoutService($gateway, new InMemoryPlanRepository($plan), new InMemorySubscriptionRepository($subscription), 'https://app.voxo.test');

        $service->startCheckout($this->scope('workspace-1'), 'business', 'awa@example.test', 'Awa Koné');

        $this->assertSame('cus_existing', $gateway->checkoutSessions()[0]['customer_id']);
    }

    #[Test]
    public function l_offre_sur_devis_ne_se_souscrit_pas_en_self_service(): void
    {
        $plan = ModelFactory::plan(['slug' => 'enterprise', 'is_custom' => true, 'provider_price_id' => null]);
        $subscription = ModelFactory::subscription(['workspace_id' => 'workspace-1']);

        $service = new CheckoutService(new ArrayPaymentGateway, new InMemoryPlanRepository($plan), new InMemorySubscriptionRepository($subscription), 'https://app.voxo.test');

        $this->expectException(PlanRequiresSalesContactException::class);

        $service->startCheckout($this->scope('workspace-1'), 'enterprise', 'awa@example.test', 'Awa Koné');
    }

    #[Test]
    public function un_palier_sans_tarif_configure_chez_le_prestataire_echoue(): void
    {
        $plan = ModelFactory::plan(['slug' => 'business', 'provider_price_id' => null]);
        $subscription = ModelFactory::subscription(['workspace_id' => 'workspace-1']);

        $service = new CheckoutService(new ArrayPaymentGateway, new InMemoryPlanRepository($plan), new InMemorySubscriptionRepository($subscription), 'https://app.voxo.test');

        $this->expectException(RuntimeException::class);

        $service->startCheckout($this->scope('workspace-1'), 'business', 'awa@example.test', 'Awa Koné');
    }
}
