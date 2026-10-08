<?php

namespace App\Domains\Widget\Http\Controllers;

use App\Domains\Conversations\Contracts\VisitorConversationContract;
use App\Domains\Widget\Http\Requests\RateConversationRequest;
use App\Domains\Widget\Services\VisitorSessionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class VisitorRatingController extends Controller
{
    public function __construct(
        private readonly VisitorSessionService $sessions,
        private readonly VisitorConversationContract $conversations,
    ) {}

    public function store(RateConversationRequest $request): Response
    {
        $claims = $this->sessions->verify($request->string('token')->toString());

        $this->conversations->rateByVisitor(
            $claims['workspaceId'],
            $request->string('conversation_id')->toString(),
            $request->integer('rating'),
            $request->string('comment')->toString() ?: null,
        );

        return response()->noContent();
    }
}
