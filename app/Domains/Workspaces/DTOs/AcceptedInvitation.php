<?php

namespace App\Domains\Workspaces\DTOs;

use App\Models\WorkspaceMember;

/**
 * Résultat de l'acceptation d'une invitation.
 *
 * `token` vaut `null` quand l'invité était déjà connecté : sa session en
 * cours a simplement été basculée sur l'espace rejoint.
 */
final readonly class AcceptedInvitation
{
    public function __construct(
        public WorkspaceMember $member,
        public ?string $token,
    ) {}
}
