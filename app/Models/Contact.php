<?php

namespace App\Models;

use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Profil client (section 2.6) : un espace peut n'en connaître que l'e-mail,
 * ou rien du tout si le visiteur n'en a jamais laissé un.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone
 * @property array<string, mixed>|null $custom_attributes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'name', 'email', 'phone', 'custom_attributes'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['custom_attributes' => 'array'];
    }

    /** @return HasMany<ContactNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(ContactNote::class);
    }

    /** @return HasMany<ContactEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(ContactEvent::class);
    }

    /** @return HasMany<Conversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
