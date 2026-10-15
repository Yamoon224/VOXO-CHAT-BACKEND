<?php

namespace App\Domains\Automations\Http\Controllers;

use App\Domains\Automations\Http\Requests\StoreAutomationScenarioRequest;
use App\Domains\Automations\Http\Requests\TestAutomationScenarioRequest;
use App\Domains\Automations\Http\Requests\UpdateAutomationScenarioRequest;
use App\Domains\Automations\Http\Resources\AutomationScenarioResource;
use App\Domains\Automations\Services\AutomationScenarioService;
use App\Domains\Automations\Services\AutomationTestService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AutomationScenarioController extends Controller
{
    public function __construct(
        private readonly AutomationScenarioService $scenarios,
        private readonly AutomationTestService $tester,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return AutomationScenarioResource::collection($this->scenarios->list(WorkspaceScope::fromRequest($request)));
    }

    public function store(StoreAutomationScenarioRequest $request): JsonResponse
    {
        $scenario = $this->scenarios->create(WorkspaceScope::fromRequest($request), $request->toAttributes());

        return (new AutomationScenarioResource($scenario))->response()->setStatusCode(201);
    }

    public function update(UpdateAutomationScenarioRequest $request, string $scenario): AutomationScenarioResource
    {
        return new AutomationScenarioResource(
            $this->scenarios->update(WorkspaceScope::fromRequest($request), $scenario, $request->toAttributes()),
        );
    }

    public function destroy(Request $request, string $scenario): Response
    {
        $this->scenarios->delete(WorkspaceScope::fromRequest($request), $scenario);

        return response()->noContent();
    }

    public function test(TestAutomationScenarioRequest $request, string $scenario): JsonResponse
    {
        $result = $this->tester->test(
            WorkspaceScope::fromRequest($request),
            $scenario,
            $request->string('message')->toString(),
            $request->string('channel')->toString() ?: null,
        );

        return response()->json(['data' => $result]);
    }
}
