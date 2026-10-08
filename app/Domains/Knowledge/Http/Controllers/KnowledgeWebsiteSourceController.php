<?php

namespace App\Domains\Knowledge\Http\Controllers;

use App\Domains\Knowledge\Http\Requests\StoreKnowledgeWebsiteSourceRequest;
use App\Domains\Knowledge\Http\Resources\KnowledgeSourceResource;
use App\Domains\Knowledge\Services\KnowledgeSourceService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class KnowledgeWebsiteSourceController extends Controller
{
    public function __construct(private readonly KnowledgeSourceService $sources) {}

    public function store(StoreKnowledgeWebsiteSourceRequest $request): JsonResponse
    {
        $source = $this->sources->createWebsiteSource(
            WorkspaceScope::fromRequest($request),
            $request->string('name')->toString() ?: null,
            $request->string('url')->toString(),
            $request->string('sitemap_url')->toString() ?: null,
            $request->frequency(),
            $request->user()->id,
        );

        return (new KnowledgeSourceResource($source))->response()->setStatusCode(201);
    }
}
