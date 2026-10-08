<?php

namespace App\Domains\Workspaces\Http\Controllers;

use App\Domains\Shared\Support\PageSize;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Workspaces\Http\Requests\ListMembersRequest;
use App\Domains\Workspaces\Http\Requests\UpdateMemberRequest;
use App\Domains\Workspaces\Http\Resources\MemberResource;
use App\Domains\Workspaces\Services\MemberService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MemberController extends Controller
{
    public function __construct(private readonly MemberService $members) {}

    public function index(ListMembersRequest $request): AnonymousResourceCollection
    {
        return MemberResource::collection($this->members->paginate(
            WorkspaceScope::fromRequest($request),
            $request->validated(),
            PageSize::from($request->query('per_page')),
        ));
    }

    public function update(UpdateMemberRequest $request, string $member): MemberResource
    {
        return new MemberResource(
            $this->members->changeRole(WorkspaceScope::fromRequest($request), $member, $request->role()),
        );
    }

    public function destroy(Request $request, string $member): Response
    {
        $this->members->remove(WorkspaceScope::fromRequest($request), $member);

        return response()->noContent();
    }
}
