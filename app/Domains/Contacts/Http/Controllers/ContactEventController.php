<?php

namespace App\Domains\Contacts\Http\Controllers;

use App\Domains\Contacts\Http\Resources\ContactEventResource;
use App\Domains\Contacts\Services\ContactService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactEventController extends Controller
{
    public function __construct(private readonly ContactService $contacts) {}

    public function index(Request $request, string $contact): AnonymousResourceCollection
    {
        return ContactEventResource::collection(
            $this->contacts->events(WorkspaceScope::fromRequest($request)->workspaceId, $contact),
        );
    }
}
