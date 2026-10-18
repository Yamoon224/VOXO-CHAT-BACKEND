<?php

namespace App\Domains\Channels\Contracts;

interface ViberChannelProviderContract
{
    /** @param array<string, mixed> $credentials */
    public function sendReply(string $toUserId, string $body, array $credentials): void;
}
