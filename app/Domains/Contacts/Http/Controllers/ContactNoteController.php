<?php

namespace App\Domains\Contacts\Http\Controllers;

use App\Domains\Contacts\Http\Requests\StoreContactNoteRequest;
use App\Domains\Contacts\Http\Resources\ContactNoteResource;
use App\Domains\Contacts\Services\ContactNoteService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactNoteController extends Controller
{
    public function __construct(private readonly ContactNoteService $notes) {}

    public function index(Request $request, string $contact): AnonymousResourceCollection
    {
        return ContactNoteResource::collection(
            $this->notes->list(WorkspaceScope::fromRequest($request)->workspaceId, $contact),
        );
    }

    public function store(StoreContactNoteRequest $request, string $contact): JsonResponse
    {
        $note = $this->notes->create(
            WorkspaceScope::fromRequest($request)->workspaceId,
            $contact,
            $request->string('body')->toString(),
            $request->user()->id,
        );

        return (new ContactNoteResource($note))->response()->setStatusCode(201);
    }
}
