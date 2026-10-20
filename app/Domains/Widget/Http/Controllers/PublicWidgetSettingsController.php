<?php

namespace App\Domains\Widget\Http\Controllers;

use App\Domains\Widget\Http\Resources\PublicWidgetSettingsResource;
use App\Domains\Widget\Services\WidgetSettingsService;
use App\Http\Controllers\Controller;

class PublicWidgetSettingsController extends Controller
{
    public function __construct(private readonly WidgetSettingsService $settings) {}

    public function show(string $workspace): PublicWidgetSettingsResource
    {
        $settings = $this->settings->forWorkspaceId($workspace);
        $this->settings->markInstalled($settings);

        return new PublicWidgetSettingsResource($settings);
    }
}
