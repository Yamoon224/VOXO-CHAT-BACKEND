<?php

namespace App\Models;

use Database\Factories\StatusSubscriberFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Abonné aux alertes de la page de statut (section 2.11) : simple inscription
 * par e-mail, désinscription par lien signé (`StatusSubscriberToken`) — pas
 * de double consentement, cohérent avec le reste de la plateforme (aucun
 * flux de la section 2.15 n'en exige un non plus).
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StatusSubscriber extends Model
{
    /** @use HasFactory<StatusSubscriberFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'email'];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
