<?php

namespace App\Models;

use App\Domains\Conversations\Enums\MessageSenderType;
use App\Domains\Conversations\Enums\MessageVisibility;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Message d'une conversation : du visiteur, d'un agent, de l'agent IA, ou du
 * système. Une note interne (`visibility = internal`) n'est jamais renvoyée
 * au visiteur.
 *
 * @property string $id
 * @property string $conversation_id
 * @property string $workspace_id
 * @property MessageSenderType $sender_type
 * @property string|null $sender_user_id
 * @property MessageVisibility $visibility
 * @property string $body
 * @property list<array<string, mixed>>|null $citations
 * @property string|null $attachment_path
 * @property string|null $attachment_filename
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'conversation_id', 'workspace_id', 'sender_type', 'sender_user_id', 'visibility',
        'body', 'citations', 'attachment_path', 'attachment_filename',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sender_type' => MessageSenderType::class,
            'visibility' => MessageVisibility::class,
            'citations' => 'array',
        ];
    }

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function isVisibleToVisitor(): bool
    {
        return $this->visibility === MessageVisibility::Public;
    }

    public function isFromVisitor(): bool
    {
        return $this->sender_type === MessageSenderType::Visitor;
    }
}
