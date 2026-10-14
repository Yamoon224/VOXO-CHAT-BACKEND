<?php

namespace App\Domains\Channels\Contracts;

/**
 * Envoi sortant du canal e-mail, derrière un contrat commun — même principe
 * que `PaymentGatewayContract` pour Stripe : un pilote concret se branche
 * sans changer ce domaine. Le pilote par défaut passe par `Illuminate\Mail`
 * (SMTP déjà configuré, section 2.15) ; un prestataire dédié (Postmark,
 * Mailgun...) pourra s'ajouter plus tard pour la réception entrante
 * notamment, sans toucher à cette interface.
 */
interface EmailChannelProviderContract
{
    public function sendReply(string $toEmail, string $fromEmail, string $fromName, string $subject, string $body): void;
}
