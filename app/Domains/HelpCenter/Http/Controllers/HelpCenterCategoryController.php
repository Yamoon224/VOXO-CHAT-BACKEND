<?php

namespace App\Domains\HelpCenter\Http\Controllers;

use App\Domains\HelpCenter\Http\Requests\StoreHelpCenterCategoryRequest;
use App\Domains\HelpCenter\Http\Requests\UpdateHelpCenterCategoryRequest;
use App\Domains\HelpCenter\Http\Resources\HelpCenterCategoryResource;
use App\Domains\HelpCenter\Services\HelpCenterCategoryService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class HelpCenterCategoryController extends Controller
{
    public function __construct(private readonly HelpCenterCategoryService $categories) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return HelpCenterCategoryResource::collection($this->categories->list(WorkspaceScope::fromRequest($request)));
    }

    public function store(StoreHelpCenterCategoryRequest $request): JsonResponse
    {
        $category = $this->categories->create(
            WorkspaceScope::fromRequest($request),
            $request->string('name')->toString(),
            $request->locale(),
            $request->position(),
        );

        return (new HelpCenterCategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(UpdateHelpCenterCategoryRequest $request, string $category): HelpCenterCategoryResource
    {
        return new HelpCenterCategoryResource($this->categories->update(
            WorkspaceScope::fromRequest($request),
            $category,
            $request->string('name')->toString(),
            $request->position(),
        ));
    }

    public function destroy(Request $request, string $category): Response
    {
        $this->categories->delete(WorkspaceScope::fromRequest($request), $category);

        return response()->noContent();
    }
}
