<?php

namespace App\Domains\Contacts\Contracts;

/**
 * Lecture étroite exposée aux domaines qui démarrent une conversation
 * (`Widget`, `Channels`) : retrouver ou créer le profil d'un visiteur à partir
 * de son e-mail, sans que ces domaines connaissent le modèle `Contact` ni ses
 * attributs personnalisés.
 */
interface ContactLinkContract
{
    /** `null` si l'e-mail est vide : pas de profil sans identité connue. */
    public function findOrCreateIdByEmail(string $workspaceId, ?string $email, ?string $name = null): ?string;

    /** Même principe par numéro de téléphone (lot 5 : WhatsApp, SMS). `null` si le numéro est vide. */
    public function findOrCreateIdByPhone(string $workspaceId, ?string $phone, ?string $name = null): ?string;

    /**
     * Même principe par identifiant externe propre au canal (lot 5 :
     * Messenger PSID, Instagram IGSID, identifiant de chat Telegram) — ces
     * canaux n'exposent ni e-mail ni téléphone côté visiteur. Stocké dans
     * `custom_attributes` sous la clé `{$channel}_id`. `null` si l'identifiant
     * est vide.
     */
    public function findOrCreateIdByExternalId(string $workspaceId, string $channel, ?string $externalId, ?string $name = null): ?string;
}
