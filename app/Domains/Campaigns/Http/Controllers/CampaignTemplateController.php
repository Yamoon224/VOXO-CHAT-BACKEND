<?php

namespace App\Domains\Campaigns\Http\Controllers;

use App\Domains\Campaigns\Http\Requests\StoreCampaignTemplateRequest;
use App\Domains\Campaigns\Http\Requests\UpdateCampaignTemplateRequest;
use App\Domains\Campaigns\Http\Resources\CampaignTemplateResource;
use App\Domains\Campaigns\Services\CampaignTemplateService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CampaignTemplateController extends Controller
{
    public function __construct(private readonly CampaignTemplateService $templates) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return CampaignTemplateResource::collection($this->templates->list(WorkspaceScope::fromRequest($request)));
    }

    public function store(StoreCampaignTemplateRequest $request): JsonResponse
    {
        $template = $this->templates->create(WorkspaceScope::fromRequest($request), $request->toAttributes());

        return (new CampaignTemplateResource($template))->response()->setStatusCode(201);
    }

    public function update(UpdateCampaignTemplateRequest $request, string $template): CampaignTemplateResource
    {
        return new CampaignTemplateResource(
            $this->templates->update(WorkspaceScope::fromRequest($request), $template, $request->toAttributes()),
        );
    }

    public function destroy(Request $request, string $template): Response
    {
        $this->templates->delete(WorkspaceScope::fromRequest($request), $template);

        return response()->noContent();
    }
}
