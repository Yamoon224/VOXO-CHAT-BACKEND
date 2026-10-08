<?php

namespace App\Domains\Assistant\Jobs;

use App\Domains\Assistant\Services\AssistantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RespondToVisitorMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $conversationId) {}

    public function handle(AssistantService $assistant): void
    {
        $assistant->processConversationResponse($this->conversationId);
    }
}
