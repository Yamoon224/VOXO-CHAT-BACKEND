<?php

namespace App\Domains\Knowledge\Http\Controllers;

use App\Domains\Knowledge\Http\Requests\ListKnowledgeSourcesRequest;
use App\Domains\Knowledge\Http\Resources\KnowledgeSourceResource;
use App\Domains\Knowledge\Services\KnowledgeSourceService;
use App\Domains\Shared\Support\PageSize;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class KnowledgeSourceController extends Controller
{
    public function __construct(private readonly KnowledgeSourceService $sources) {}

    public function index(ListKnowledgeSourcesRequest $request): AnonymousResourceCollection
    {
        return KnowledgeSourceResource::collection($this->sources->list(
            WorkspaceScope::fromRequest($request),
            $request->validated(),
            PageSize::from($request->query('per_page')),
        ));
    }

    public function destroy(Request $request, string $source): Response
    {
        $this->sources->delete(WorkspaceScope::fromRequest($request), $source);

        return response()->noContent();
    }

    public function recrawl(Request $request, string $source): Response
    {
        $this->sources->recrawl(WorkspaceScope::fromRequest($request), $source);

        return response()->noContent();
    }
}
