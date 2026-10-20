<?php

namespace Tests\Unit\Realtime;

use App\Domains\Realtime\Events\MessageBroadcastEvent;
use App\Models\Message;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MessageBroadcastEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_message_public_est_diffuse_sur_les_deux_canaux(): void
    {
        $message = Message::factory()->create();

        $channels = (new MessageBroadcastEvent($message))->broadcastOn();

        $this->assertCount(2, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame("private-conversation.{$message->conversation_id}", $channels[0]->name);
        $this->assertInstanceOf(PresenceChannel::class, $channels[1]);
        $this->assertSame("presence-conversation.{$message->conversation_id}.public", $channels[1]->name);
    }

    #[Test]
    public function une_note_interne_n_est_diffusee_que_sur_le_canal_agent(): void
    {
        $message = Message::factory()->internal()->create();

        $channels = (new MessageBroadcastEvent($message))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame("private-conversation.{$message->conversation_id}", $channels[0]->name);
    }

    #[Test]
    public function le_nom_d_evenement_est_message_created(): void
    {
        $message = Message::factory()->create();

        $this->assertSame('message.created', (new MessageBroadcastEvent($message))->broadcastAs());
    }

    #[Test]
    public function la_charge_utile_porte_les_champs_du_message(): void
    {
        $message = Message::factory()->create(['body' => 'Bonjour.']);

        $payload = (new MessageBroadcastEvent($message))->broadcastWith();

        $this->assertSame($message->id, $payload['id']);
        $this->assertSame($message->conversation_id, $payload['conversation_id']);
        $this->assertSame('Bonjour.', $payload['body']);
        $this->assertSame('public', $payload['visibility']);
    }
}
