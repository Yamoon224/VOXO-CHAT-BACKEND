<?php

namespace App\Domains\Contacts\Http\Controllers;

use App\Domains\Contacts\Http\Requests\ListContactsRequest;
use App\Domains\Contacts\Http\Requests\StoreContactRequest;
use App\Domains\Contacts\Http\Requests\UpdateContactRequest;
use App\Domains\Contacts\Http\Resources\ContactResource;
use App\Domains\Contacts\Services\ContactService;
use App\Domains\Shared\Support\PageSize;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ContactController extends Controller
{
    public function __construct(private readonly ContactService $contacts) {}

    public function index(ListContactsRequest $request): AnonymousResourceCollection
    {
        return ContactResource::collection($this->contacts->paginate(
            WorkspaceScope::fromRequest($request)->workspaceId,
            $request->validated(),
            PageSize::from($request->query('per_page')),
        ));
    }

    public function show(Request $request, string $contact): ContactResource
    {
        return new ContactResource($this->contacts->find(WorkspaceScope::fromRequest($request)->workspaceId, $contact));
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = $this->contacts->create(WorkspaceScope::fromRequest($request)->workspaceId, $request->validated());

        return (new ContactResource($contact))->response()->setStatusCode(201);
    }

    public function update(UpdateContactRequest $request, string $contact): ContactResource
    {
        return new ContactResource(
            $this->contacts->update(WorkspaceScope::fromRequest($request)->workspaceId, $contact, $request->validated()),
        );
    }

    public function destroy(Request $request, string $contact): Response
    {
        $this->contacts->delete(WorkspaceScope::fromRequest($request)->workspaceId, $contact);

        return response()->noContent();
    }
}
