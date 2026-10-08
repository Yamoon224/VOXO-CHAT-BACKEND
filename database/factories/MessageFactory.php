<?php

namespace Database\Factories;

use App\Domains\Conversations\Enums\MessageSenderType;
use App\Domains\Conversations\Enums\MessageVisibility;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Message> */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $conversation = Conversation::factory()->create();

        return [
            'conversation_id' => $conversation->id,
            'workspace_id' => $conversation->workspace_id,
            'sender_type' => MessageSenderType::Visitor,
            'visibility' => MessageVisibility::Public,
            'body' => $this->faker->sentence(),
        ];
    }

    public function fromAgent(string $userId): static
    {
        return $this->state(fn () => ['sender_type' => MessageSenderType::Agent, 'sender_user_id' => $userId]);
    }

    public function fromAi(): static
    {
        return $this->state(fn () => ['sender_type' => MessageSenderType::Ai]);
    }

    public function internal(): static
    {
        return $this->state(fn () => ['visibility' => MessageVisibility::Internal]);
    }
}
