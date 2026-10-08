<?php

namespace App\Domains\Workspaces\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/**
 * Un membre ne change pas son propre rôle et ne se retire pas lui-même : un administrateur distrait se verrouillerait hors de son équipe.
 */
final class SelfMembershipChangeException extends DomainException
{
    public static function make(): self
    {
        return new self('Vous ne pouvez pas modifier votre propre adhésion.', 'self_membership_change', 409);
    }
}
