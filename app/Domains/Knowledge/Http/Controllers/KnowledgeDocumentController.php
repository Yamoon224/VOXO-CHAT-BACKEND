<?php

namespace App\Domains\Knowledge\Http\Controllers;

use App\Domains\Knowledge\Http\Requests\ListKnowledgeDocumentsRequest;
use App\Domains\Knowledge\Http\Resources\KnowledgeDocumentResource;
use App\Domains\Knowledge\Services\KnowledgeDocumentService;
use App\Domains\Shared\Support\PageSize;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class KnowledgeDocumentController extends Controller
{
    public function __construct(private readonly KnowledgeDocumentService $documents) {}

    public function index(ListKnowledgeDocumentsRequest $request): AnonymousResourceCollection
    {
        return KnowledgeDocumentResource::collection($this->documents->list(
            WorkspaceScope::fromRequest($request),
            $request->validated(),
            PageSize::from($request->query('per_page')),
        ));
    }

    public function show(Request $request, string $document): KnowledgeDocumentResource
    {
        return new KnowledgeDocumentResource(
            $this->documents->get(WorkspaceScope::fromRequest($request), $document),
        );
    }

    public function destroy(Request $request, string $document): Response
    {
        $this->documents->delete(WorkspaceScope::fromRequest($request), $document);

        return response()->noContent();
    }

    public function retry(Request $request, string $document): KnowledgeDocumentResource
    {
        return new KnowledgeDocumentResource(
            $this->documents->retry(WorkspaceScope::fromRequest($request), $document),
        );
    }
}
