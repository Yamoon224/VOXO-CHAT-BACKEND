<?php

namespace App\Domains\Knowledge\Http\Controllers;

use App\Domains\Knowledge\Contracts\KnowledgeSearchContract;
use App\Domains\Knowledge\Http\Requests\StoreKnowledgeSearchRequest;
use App\Domains\Knowledge\Http\Resources\KnowledgeSearchResultResource;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class KnowledgeSearchController extends Controller
{
    public function __construct(private readonly KnowledgeSearchContract $search) {}

    public function store(StoreKnowledgeSearchRequest $request): AnonymousResourceCollection
    {
        $results = $this->search->search(
            WorkspaceScope::fromRequest($request),
            $request->string('query')->toString(),
            $request->limit(),
        );

        return KnowledgeSearchResultResource::collection($results);
    }
}
