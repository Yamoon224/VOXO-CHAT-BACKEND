<?php

namespace App\Domains\Campaigns\Exceptions;

use App\Domains\Shared\Exceptions\DomainException;

/** Une campagne envoyée fige son ciblage et son contenu : pas de second envoi, pas de nouvelle modification. */
final class CampaignAlreadySentException extends DomainException
{
    public static function make(): self
    {
        return new self('Cette campagne a déjà été envoyée.', 'campaign_already_sent', 422);
    }
}
