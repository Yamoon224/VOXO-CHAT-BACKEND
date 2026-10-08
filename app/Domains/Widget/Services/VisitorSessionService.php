<?php

namespace App\Domains\Widget\Services;

use App\Domains\Widget\Exceptions\VisitorSessionInvalidException;
use App\Domains\Widget\Support\VisitorSessionToken;
use Illuminate\Support\Str;

final class VisitorSessionService
{
    public function __construct(private readonly VisitorSessionToken $tokens) {}

    /** @return array{token: string, visitor_id: string} */
    public function start(string $workspaceId): array
    {
        $visitorId = (string) Str::uuid();

        return ['token' => $this->tokens->issue($workspaceId, $visitorId, now()), 'visitor_id' => $visitorId];
    }

    /**
     * @return array{workspaceId: string, visitorId: string}
     *
     * @throws VisitorSessionInvalidException
     */
    public function verify(string $token): array
    {
        return $this->tokens->verify($token, now()) ?? throw VisitorSessionInvalidException::make();
    }
}
