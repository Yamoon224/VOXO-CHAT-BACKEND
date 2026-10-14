<?php

namespace App\Domains\Channels\Http\Controllers;

use App\Domains\Channels\Http\Requests\ContactFormSubmissionRequest;
use App\Domains\Channels\Services\InboundChannelService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** Formulaire de contact public d'un espace de travail (section 2.5) : même shape qu'un e-mail entrant, sans prestataire. */
class ContactFormController extends Controller
{
    public function __construct(private readonly InboundChannelService $inbound) {}

    public function store(ContactFormSubmissionRequest $request, string $workspace): JsonResponse
    {
        $conversation = $this->inbound->receive(
            $workspace,
            'contact_form',
            $request->string('email')->toString(),
            $request->string('name')->toString() ?: null,
            $request->string('message')->toString(),
        );

        return response()->json(['data' => ['conversation_id' => $conversation->id]], 201);
    }
}
