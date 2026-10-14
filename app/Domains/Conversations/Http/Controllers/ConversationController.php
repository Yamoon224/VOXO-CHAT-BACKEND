<?php

namespace App\Domains\Conversations\Http\Controllers;

use App\Domains\Assistant\Contracts\ConversationInsightContract;
use App\Domains\Conversations\Http\Requests\ListConversationsRequest;
use App\Domains\Conversations\Http\Requests\UpdateConversationAssignmentRequest;
use App\Domains\Conversations\Http\Requests\UpdateConversationStatusRequest;
use App\Domains\Conversations\Http\Resources\ConversationResource;
use App\Domains\Conversations\Services\ConversationService;
use App\Domains\Shared\Support\PageSize;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConversationController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversations,
        private readonly ConversationInsightContract $insights,
    ) {}

    public function index(ListConversationsRequest $request): AnonymousResourceCollection
    {
        return ConversationResource::collection($this->conversations->list(
            WorkspaceScope::fromRequest($request),
            $request->validated(),
            PageSize::from($request->query('per_page')),
        ));
    }

    public function show(Request $request, string $conversation): ConversationResource
    {
        return new ConversationResource($this->conversations->get(WorkspaceScope::fromRequest($request), $conversation));
    }

    public function updateStatus(UpdateConversationStatusRequest $request, string $conversation): ConversationResource
    {
        return new ConversationResource(
            $this->conversations->changeStatus(WorkspaceScope::fromRequest($request), $conversation, $request->status()),
        );
    }

    public function updateAssignment(UpdateConversationAssignmentRequest $request, string $conversation): ConversationResource
    {
        return new ConversationResource(
            $this->conversations->assign(WorkspaceScope::fromRequest($request), $conversation, $request->userId()),
        );
    }

    public function summarize(Request $request, string $conversation): ConversationResource
    {
        $scope = WorkspaceScope::fromRequest($request);

        return new ConversationResource($this->insights->summarize($scope->workspaceId, $conversation));
    }

    public function analyzeSentiment(Request $request, string $conversation): ConversationResource
    {
        $scope = WorkspaceScope::fromRequest($request);

        return new ConversationResource($this->insights->analyzeSentiment($scope->workspaceId, $conversation));
    }

    /**
     * Copilote (section 2.2) : une suggestion, jamais un envoi — l'agent
     * reste décisionnaire. Pas de ressource dédiée pour ce DTO : il n'a pas
     * vocation à être connu de ce domaine au-delà du contrat qui le produit.
     */
    public function suggestReply(Request $request, string $conversation): JsonResponse
    {
        $scope = WorkspaceScope::fromRequest($request);
        $suggestion = $this->insights->suggestReply($scope->workspaceId, $conversation);

        return response()->json(['data' => [
            'content' => $suggestion->content,
            'citations' => $suggestion->citations,
            'confidence' => $suggestion->confidence,
        ]]);
    }
}
