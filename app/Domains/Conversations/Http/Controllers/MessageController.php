<?php

namespace App\Domains\Conversations\Http\Controllers;

use App\Domains\Conversations\Http\Requests\StoreMessageRequest;
use App\Domains\Conversations\Http\Resources\MessageResource;
use App\Domains\Conversations\Services\MessageService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MessageController extends Controller
{
    public function __construct(private readonly MessageService $messages) {}

    public function index(Request $request, string $conversation): AnonymousResourceCollection
    {
        return MessageResource::collection($this->messages->listForAgent(WorkspaceScope::fromRequest($request), $conversation));
    }

    public function store(StoreMessageRequest $request, string $conversation): JsonResponse
    {
        $message = $this->messages->postAgentMessage(
            WorkspaceScope::fromRequest($request),
            $conversation,
            $request->string('body')->toString(),
            $request->visibility(),
        );

        return (new MessageResource($message))->response()->setStatusCode(201);
    }
}
