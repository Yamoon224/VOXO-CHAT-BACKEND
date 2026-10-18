<?php

namespace App\Domains\StatusPage\Http\Controllers;

use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\StatusPage\Http\Resources\StatusSubscriberResource;
use App\Domains\StatusPage\Services\StatusSubscriberService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class StatusSubscriberController extends Controller
{
    public function __construct(private readonly StatusSubscriberService $subscribers) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return StatusSubscriberResource::collection($this->subscribers->list(WorkspaceScope::fromRequest($request)));
    }

    public function destroy(Request $request, string $subscriber): Response
    {
        $this->subscribers->remove(WorkspaceScope::fromRequest($request), $subscriber);

        return response()->noContent();
    }
}
