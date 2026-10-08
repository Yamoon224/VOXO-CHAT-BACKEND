<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Jeton d'accès personnel.
 *
 * Le jeton porte l'espace de travail dans lequel son titulaire agit : c'est
 * de là, et de nulle part ailleurs, que se déduit le périmètre d'une requête.
 *
 * L'identifiant voyage en clair dans l'en-tête `Authorization` ; un UUID n'y
 * annonce pas combien de jetons ont été émis.
 *
 * @property string $id
 * @property string|null $workspace_id
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUuids;
}
