<?php

namespace App\Domains\HelpCenter\Http\Controllers;

use App\Domains\HelpCenter\Http\Requests\ListHelpCenterArticlesRequest;
use App\Domains\HelpCenter\Http\Requests\StoreHelpCenterArticleRequest;
use App\Domains\HelpCenter\Http\Requests\UpdateHelpCenterArticleRequest;
use App\Domains\HelpCenter\Http\Resources\HelpCenterArticleResource;
use App\Domains\HelpCenter\Services\HelpCenterArticleService;
use App\Domains\Shared\Support\PageSize;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class HelpCenterArticleController extends Controller
{
    public function __construct(private readonly HelpCenterArticleService $articles) {}

    public function index(ListHelpCenterArticlesRequest $request): AnonymousResourceCollection
    {
        return HelpCenterArticleResource::collection($this->articles->list(
            WorkspaceScope::fromRequest($request),
            $request->validated(),
            PageSize::from($request->query('per_page')),
        ));
    }

    public function show(Request $request, string $article): HelpCenterArticleResource
    {
        return new HelpCenterArticleResource($this->articles->get(WorkspaceScope::fromRequest($request), $article));
    }

    public function store(StoreHelpCenterArticleRequest $request): JsonResponse
    {
        $article = $this->articles->create(WorkspaceScope::fromRequest($request), $request->toAttributes());

        return (new HelpCenterArticleResource($article))->response()->setStatusCode(201);
    }

    public function update(UpdateHelpCenterArticleRequest $request, string $article): HelpCenterArticleResource
    {
        return new HelpCenterArticleResource(
            $this->articles->update(WorkspaceScope::fromRequest($request), $article, $request->toAttributes()),
        );
    }

    public function destroy(Request $request, string $article): Response
    {
        $this->articles->delete(WorkspaceScope::fromRequest($request), $article);

        return response()->noContent();
    }
}
