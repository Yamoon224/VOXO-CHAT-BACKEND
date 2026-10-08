<?php

namespace App\Models;

use App\Domains\Conversations\Enums\ConversationChannel;
use App\Domains\Conversations\Enums\ConversationStatus;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Conversation d'un visiteur avec l'espace de travail, tous canaux confondus
 * (un seul canal câblé pour l'instant : `widget`).
 *
 * @property string $id
 * @property string $workspace_id
 * @property ConversationChannel $channel
 * @property ConversationStatus $status
 * @property string $visitor_id
 * @property string|null $visitor_name
 * @property string|null $visitor_email
 * @property string|null $assigned_user_id
 * @property string|null $subject
 * @property string|null $summary
 * @property string|null $sentiment
 * @property bool $needs_human
 * @property int|null $rating
 * @property string|null $rating_comment
 * @property Carbon|null $last_message_at
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'workspace_id', 'channel', 'status', 'visitor_id', 'visitor_name', 'visitor_email',
        'assigned_user_id', 'subject', 'summary', 'sentiment', 'needs_human', 'rating',
        'rating_comment', 'last_message_at', 'resolved_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'channel' => ConversationChannel::class,
            'status' => ConversationStatus::class,
            'needs_human' => 'boolean',
            'rating' => 'integer',
            'last_message_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }
}
