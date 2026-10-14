<?php

namespace App\Models;

use App\Domains\Tickets\Enums\TicketPriority;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ticket (section 2.7) : habille une conversation de priorité et d'échéance
 * SLA, sans dupliquer son statut ni son canal — voir la migration.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $conversation_id
 * @property string|null $contact_id
 * @property TicketPriority $priority
 * @property Carbon|null $due_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'conversation_id', 'contact_id', 'priority', 'due_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['priority' => TicketPriority::class, 'due_at' => 'datetime'];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
