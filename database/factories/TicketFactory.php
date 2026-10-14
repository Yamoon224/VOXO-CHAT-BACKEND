<?php

namespace Database\Factories;

use App\Domains\Tickets\Enums\TicketPriority;
use App\Models\Conversation;
use App\Models\Ticket;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ticket> */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'conversation_id' => Conversation::factory(),
            'priority' => TicketPriority::Normal,
        ];
    }
}
