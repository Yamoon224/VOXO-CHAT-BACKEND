<?php

namespace App\Models;

use Database\Factories\ContactNoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Note libre sur un contact (section 2.6).
 *
 * @property string $id
 * @property string $contact_id
 * @property string $workspace_id
 * @property string|null $author_user_id
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $author
 */
class ContactNote extends Model
{
    /** @use HasFactory<ContactNoteFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['contact_id', 'workspace_id', 'author_user_id', 'body'];

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
