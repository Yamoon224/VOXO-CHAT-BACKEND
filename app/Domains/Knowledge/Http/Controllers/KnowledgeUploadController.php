<?php

namespace App\Domains\Knowledge\Http\Controllers;

use App\Domains\Knowledge\Http\Requests\StoreKnowledgeUploadRequest;
use App\Domains\Knowledge\Http\Resources\KnowledgeSourceResource;
use App\Domains\Knowledge\Services\KnowledgeSourceService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class KnowledgeUploadController extends Controller
{
    public function __construct(private readonly KnowledgeSourceService $sources) {}

    public function store(StoreKnowledgeUploadRequest $request): JsonResponse
    {
        $source = $this->sources->createUploadSource(
            WorkspaceScope::fromRequest($request),
            $request->string('name')->toString(),
            $request->file('files'),
            $request->user()->id,
        );

        return (new KnowledgeSourceResource($source))->response()->setStatusCode(201);
    }
}
