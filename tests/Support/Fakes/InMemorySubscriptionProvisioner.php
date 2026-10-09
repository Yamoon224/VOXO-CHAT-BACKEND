<?php

namespace Tests\Support\Fakes;

use App\Domains\Billing\Contracts\SubscriptionProvisionerContract;

final class InMemorySubscriptionProvisioner implements SubscriptionProvisionerContract
{
    /** @var list<string> */
    public array $trialsStartedFor = [];

    public function startTrial(string $workspaceId): void
    {
        $this->trialsStartedFor[] = $workspaceId;
    }
}
