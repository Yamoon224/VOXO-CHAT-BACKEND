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
}
