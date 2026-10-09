<?php

namespace App\Domains\Platform\Http\Controllers;

use App\Domains\Platform\Http\Resources\PlatformWorkspaceResource;
use App\Domains\Platform\Services\PlatformWorkspaceService;
use App\Domains\Shared\Support\PageSize;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlatformWorkspaceController extends Controller
{
    public function __construct(private readonly PlatformWorkspaceService $workspaces) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return PlatformWorkspaceResource::collection(
            $this->workspaces->listPaginated(PageSize::from($request->query('per_page'))),
        );
    }
}
