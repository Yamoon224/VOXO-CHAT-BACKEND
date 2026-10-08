<?php

namespace App\Domains\Widget\Http\Controllers;

use App\Domains\Conversations\Contracts\ConversationRepositoryContract;
use App\Domains\Conversations\Contracts\MessageRepositoryContract;
use App\Domains\Conversations\Contracts\VisitorConversationContract;
use App\Domains\Widget\Http\Requests\ListVisitorMessagesRequest;
use App\Domains\Widget\Http\Requests\PostVisitorMessageRequest;
use App\Domains\Widget\Http\Resources\PublicMessageResource;
use App\Domains\Widget\Services\VisitorSessionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VisitorMessageController extends Controller
{
    public function __construct(
        private readonly VisitorSessionService $sessions,
        private readonly VisitorConversationContract $conversations,
        private readonly ConversationRepositoryContract $conversationsRepository,
        private readonly MessageRepositoryContract $messages,
    ) {}

    public function store(PostVisitorMessageRequest $request): JsonResponse
    {
        $claims = $this->sessions->verify($request->string('token')->toString());

        $conversation = $this->conversations->receiveVisitorMessage(
            $claims['workspaceId'],
            $claims['visitorId'],
            $request->string('body')->toString(),
            $request->string('visitor_name')->toString() ?: null,
            $request->string('visitor_email')->toString() ?: null,
        );

        return response()->json(['data' => ['conversation_id' => $conversation->id]], 201);
    }

    public function index(ListVisitorMessagesRequest $request): AnonymousResourceCollection
    {
        $claims = $this->sessions->verify($request->string('token')->toString());

        $conversation = $this->conversationsRepository->findLatestForVisitor($claims['workspaceId'], $claims['visitorId']);

        return PublicMessageResource::collection(
            $conversation === null ? [] : $this->messages->publicForConversation($conversation->id),
        );
    }
}
