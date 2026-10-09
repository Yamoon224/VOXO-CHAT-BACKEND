<?php

namespace App\Domains\Billing\Http\Controllers;

use App\Domains\Billing\Http\Resources\SubscriptionResource;
use App\Domains\Billing\Services\SubscriptionService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function show(Request $request): SubscriptionResource
    {
        $scope = WorkspaceScope::fromRequest($request);

        return new SubscriptionResource(
            $this->subscriptions->current($scope),
            $this->subscriptions->aiCreditBalance($scope->workspaceId),
        );
    }

    public function switchToFree(Request $request): SubscriptionResource
    {
        $scope = WorkspaceScope::fromRequest($request);

        return new SubscriptionResource(
            $this->subscriptions->switchToFreePlan($scope),
            $this->subscriptions->aiCreditBalance($scope->workspaceId),
        );
    }

    public function cancel(Request $request): SubscriptionResource
    {
        $scope = WorkspaceScope::fromRequest($request);

        return new SubscriptionResource(
            $this->subscriptions->cancel($scope),
            $this->subscriptions->aiCreditBalance($scope->workspaceId),
        );
    }
}
