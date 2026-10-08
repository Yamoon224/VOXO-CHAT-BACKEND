<?php

namespace App\Domains\Notifications\Contracts;

/**
 * E-mails transactionnels que les autres domaines peuvent déclencher.
 *
 * Les appelants fournissent des faits (une adresse, un jeton) ; la rédaction,
 * la construction des liens et le choix du pilote restent ici.
 */
interface TransactionalMailerContract
{
    public function sendEmailVerification(string $email, string $name, string $token): void;

    public function sendPasswordReset(string $email, string $name, string $token): void;

    public function sendWorkspaceInvitation(
        string $email,
        string $workspaceName,
        string $inviterName,
        string $roleLabel,
        string $token,
    ): void;
}
