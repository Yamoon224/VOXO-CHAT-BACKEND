<?php

namespace App\Domains\Campaigns\Http\Controllers;

use App\Domains\Campaigns\Http\Requests\StoreCampaignRequest;
use App\Domains\Campaigns\Http\Requests\UpdateCampaignRequest;
use App\Domains\Campaigns\Http\Resources\CampaignResource;
use App\Domains\Campaigns\Http\Resources\CampaignSendResource;
use App\Domains\Campaigns\Services\CampaignService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CampaignController extends Controller
{
    public function __construct(private readonly CampaignService $campaigns) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return CampaignResource::collection($this->campaigns->list(WorkspaceScope::fromRequest($request)));
    }

    public function show(Request $request, string $campaign): CampaignResource
    {
        return new CampaignResource($this->campaigns->get(WorkspaceScope::fromRequest($request), $campaign));
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = $this->campaigns->create(WorkspaceScope::fromRequest($request), $request->toAttributes());

        return (new CampaignResource($campaign))->response()->setStatusCode(201);
    }

    public function update(UpdateCampaignRequest $request, string $campaign): CampaignResource
    {
        return new CampaignResource(
            $this->campaigns->update(WorkspaceScope::fromRequest($request), $campaign, $request->toAttributes()),
        );
    }

    public function destroy(Request $request, string $campaign): Response
    {
        $this->campaigns->delete(WorkspaceScope::fromRequest($request), $campaign);

        return response()->noContent();
    }

    public function send(Request $request, string $campaign): CampaignResource
    {
        return new CampaignResource($this->campaigns->send(WorkspaceScope::fromRequest($request), $campaign));
    }

    public function sends(Request $request, string $campaign): AnonymousResourceCollection
    {
        return CampaignSendResource::collection($this->campaigns->sends(WorkspaceScope::fromRequest($request), $campaign));
    }
}
