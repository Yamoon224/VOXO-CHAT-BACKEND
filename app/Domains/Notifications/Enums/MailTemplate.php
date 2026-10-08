<?php

namespace App\Domains\Notifications\Enums;

/**
 * E-mails transactionnels de la plateforme.
 *
 * Chaque cas désigne une vue Blade et porte son objet : ajouter un e-mail
 * revient à ajouter un cas et sa vue, sans toucher aux pilotes d'envoi.
 */
enum MailTemplate: string
{
    case EmailVerification = 'email_verification';
    case PasswordReset = 'password_reset';
    case WorkspaceInvitation = 'workspace_invitation';

    public function view(): string
    {
        return 'mail.'.$this->value;
    }

    /** @param  array<string, mixed>  $data */
    public function subject(array $data = []): string
    {
        return match ($this) {
            self::EmailVerification => 'Confirmez votre adresse e-mail',
            self::PasswordReset => 'Réinitialisation de votre mot de passe',
            self::WorkspaceInvitation => sprintf(
                'Invitation à rejoindre %s sur VOXO',
                is_string($data['workspace_name'] ?? null) ? $data['workspace_name'] : 'un espace de travail',
            ),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
