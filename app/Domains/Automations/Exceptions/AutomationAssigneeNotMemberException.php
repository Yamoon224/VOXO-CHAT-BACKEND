<?php

namespace App\Domains\Automations\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Une étape « affecter à un agent » ne peut désigner qu'un membre de l'espace de travail. */
final class AutomationAssigneeNotMemberException extends DomainException
{
    public static function make(): self
    {
        return new self("Cette personne n'est pas membre de l'espace de travail.", 'automation_assignee_not_member', 422);
    }
}
