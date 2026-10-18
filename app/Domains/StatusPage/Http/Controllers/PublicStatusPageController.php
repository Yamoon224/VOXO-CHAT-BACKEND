<?php

namespace App\Domains\StatusPage\Http\Controllers;

use App\Domains\StatusPage\Http\Resources\PublicStatusOverviewResource;
use App\Domains\StatusPage\Services\PublicStatusPageService;
use App\Http\Controllers\Controller;

class PublicStatusPageController extends Controller
{
    public function __construct(private readonly PublicStatusPageService $overview) {}

    public function show(string $workspace): PublicStatusOverviewResource
    {
        return new PublicStatusOverviewResource($this->overview->forWorkspace($workspace));
    }
}
