<?php

namespace App\Domains\StatusPage\Http\Controllers;

use App\Domains\StatusPage\Http\Requests\SubscribeStatusAlertsRequest;
use App\Domains\StatusPage\Http\Requests\UnsubscribeStatusAlertsRequest;
use App\Domains\StatusPage\Services\StatusSubscriberService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class PublicStatusSubscriptionController extends Controller
{
    public function __construct(private readonly StatusSubscriberService $subscribers) {}

    public function store(SubscribeStatusAlertsRequest $request, string $workspace): Response
    {
        $this->subscribers->subscribe($workspace, $request->string('email')->toString());

        return response()->noContent();
    }

    /**
     * Toujours `204`, jeton valide ou non : ne jamais révéler si un jeton
     * correspond à une inscription réelle à qui ne la détient pas déjà.
     */
    public function destroy(UnsubscribeStatusAlertsRequest $request): Response
    {
        $this->subscribers->unsubscribe($request->string('token')->toString());

        return response()->noContent();
    }
}
