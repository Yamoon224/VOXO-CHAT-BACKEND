<?php

namespace Database\Factories;

use App\Domains\Conversations\Enums\ConversationChannel;
use App\Domains\Conversations\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Conversation> */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'channel' => ConversationChannel::Widget,
            'status' => ConversationStatus::Open,
            'visitor_id' => (string) Str::uuid(),
            'last_message_at' => now(),
        ];
    }

    public function assignedTo(string $userId): static
    {
        return $this->state(fn () => ['assigned_user_id' => $userId]);
    }

    public function needingHuman(): static
    {
        return $this->state(fn () => ['needs_human' => true]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => ['status' => ConversationStatus::Resolved, 'resolved_at' => now()]);
    }
}
