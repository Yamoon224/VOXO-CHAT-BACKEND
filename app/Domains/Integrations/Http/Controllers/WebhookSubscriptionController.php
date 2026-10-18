<?php

namespace App\Domains\Integrations\Http\Controllers;

use App\Domains\Integrations\Http\Requests\StoreWebhookSubscriptionRequest;
use App\Domains\Integrations\Http\Requests\UpdateWebhookSubscriptionRequest;
use App\Domains\Integrations\Http\Resources\WebhookDeliveryResource;
use App\Domains\Integrations\Http\Resources\WebhookSubscriptionResource;
use App\Domains\Integrations\Services\WebhookSubscriptionService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WebhookSubscriptionController extends Controller
{
    public function __construct(private readonly WebhookSubscriptionService $subscriptions) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return WebhookSubscriptionResource::collection($this->subscriptions->list(WorkspaceScope::fromRequest($request)));
    }

    public function store(StoreWebhookSubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptions->create(WorkspaceScope::fromRequest($request), $request->toAttributes());

        return (new WebhookSubscriptionResource($subscription))->response()->setStatusCode(201);
    }

    public function update(UpdateWebhookSubscriptionRequest $request, string $subscription): WebhookSubscriptionResource
    {
        return new WebhookSubscriptionResource(
            $this->subscriptions->update(WorkspaceScope::fromRequest($request), $subscription, $request->toAttributes()),
        );
    }

    public function destroy(Request $request, string $subscription): Response
    {
        $this->subscriptions->delete(WorkspaceScope::fromRequest($request), $subscription);

        return response()->noContent();
    }

    public function deliveries(Request $request, string $subscription): AnonymousResourceCollection
    {
        return WebhookDeliveryResource::collection(
            $this->subscriptions->deliveries(WorkspaceScope::fromRequest($request), $subscription),
        );
    }
}
