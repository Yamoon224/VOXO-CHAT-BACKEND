<?php

namespace App\Models;

use Database\Factories\ContactEventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Évènement de l'historique chronologique d'un contact (section 2.6) :
 * `conversation_started`, `conversation_resolved`, `imported`, `note_added`.
 *
 * @property string $id
 * @property string $contact_id
 * @property string $workspace_id
 * @property string $type
 * @property array<string, mixed>|null $payload
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 */
class ContactEvent extends Model
{
    /** @use HasFactory<ContactEventFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['contact_id', 'workspace_id', 'type', 'payload', 'occurred_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'array', 'occurred_at' => 'datetime'];
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
