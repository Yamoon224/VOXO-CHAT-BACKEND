<?php

namespace App\Domains\Knowledge\Http\Controllers;

use App\Domains\Knowledge\Http\Requests\ListKnowledgeDocumentsRequest;
use App\Domains\Knowledge\Http\Requests\StoreKnowledgeQaEntryRequest;
use App\Domains\Knowledge\Http\Requests\UpdateKnowledgeQaEntryRequest;
use App\Domains\Knowledge\Http\Resources\KnowledgeQaEntryResource;
use App\Domains\Knowledge\Services\KnowledgeDocumentService;
use App\Domains\Shared\Support\PageSize;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class KnowledgeQaEntryController extends Controller
{
    public function __construct(private readonly KnowledgeDocumentService $documents) {}

    public function index(ListKnowledgeDocumentsRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $filters['type'] = 'qa';

        return KnowledgeQaEntryResource::collection($this->documents->list(
            WorkspaceScope::fromRequest($request),
            $filters,
            PageSize::from($request->query('per_page')),
        ));
    }

    public function store(StoreKnowledgeQaEntryRequest $request): JsonResponse
    {
        $entry = $this->documents->createQaEntry(
            WorkspaceScope::fromRequest($request),
            $request->string('question')->toString(),
            $request->string('answer')->toString(),
            $request->user()->id,
        );

        return (new KnowledgeQaEntryResource($entry))->response()->setStatusCode(201);
    }

    public function update(UpdateKnowledgeQaEntryRequest $request, string $document): KnowledgeQaEntryResource
    {
        return new KnowledgeQaEntryResource($this->documents->updateQaEntry(
            WorkspaceScope::fromRequest($request),
            $document,
            $request->string('question')->toString(),
            $request->string('answer')->toString(),
        ));
    }

    public function destroy(Request $request, string $document): Response
    {
        $this->documents->delete(WorkspaceScope::fromRequest($request), $document);

        return response()->noContent();
    }
}
