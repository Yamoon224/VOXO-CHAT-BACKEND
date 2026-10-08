<?php

namespace App\Domains\Shared\Enums;

/**
 * Rôle d'un membre dans un espace de travail.
 *
 * Le rôle `platform_admin` n'apparaît pas ici : il ne s'exerce dans aucun
 * espace de travail en particulier et s'attribue au compte lui-même.
 */
enum WorkspaceRole: string
{
    case Owner = 'workspace_owner';
    case Admin = 'workspace_admin';
    case Agent = 'agent';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Propriétaire',
            self::Admin => 'Administrateur',
            self::Agent => 'Agent',
            self::Viewer => 'Lecteur',
        };
    }

    /**
     * Un espace de travail a un propriétaire, désigné à la création : ce rôle
     * ne s'attribue ni par invitation ni par changement de rôle.
     */
    public function isAssignable(): bool
    {
        return $this !== self::Owner;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return list<string> */
    public static function assignableValues(): array
    {
        return array_values(array_map(
            fn (self $role) => $role->value,
            array_filter(self::cases(), fn (self $role) => $role->isAssignable()),
        ));
    }
}
