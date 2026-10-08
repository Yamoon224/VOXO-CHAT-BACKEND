<?php

namespace Database\Seeders;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôles et permissions de la plateforme.
 *
 * Ce seeder est la source de vérité des droits : ils ne se modifient pas en
 * production. Chaque lot fonctionnel y ajoute les permissions qu'il applique
 * réellement ; aucune n'est déclarée d'avance.
 *
 * Les rôles d'espace de travail s'attribuent par adhésion
 * (`workspace_members.role`), jamais directement au compte. Seul
 * `platform_admin` est porté par le compte.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public const GUARD = 'web';

    /**
     * Permission => raison d'être.
     *
     * @var array<string, string>
     */
    public const PERMISSIONS = [
        'workspace.view' => "Consulter l'espace de travail et ses réglages.",
        'workspace.manage' => "Modifier les réglages de l'espace de travail.",
        'members.view' => "Consulter les membres de l'équipe.",
        'members.manage' => 'Inviter, changer de rôle et retirer des membres.',
        'platform.manage' => 'Administrer la plateforme : espaces, plans, consommation.',
        'knowledge.view' => 'Consulter la base de connaissances et effectuer des recherches.',
        'knowledge.manage' => 'Importer, explorer, modifier et supprimer le contenu de la base de connaissances.',
    ];

    /**
     * Rôle => permissions.
     *
     * @var array<string, list<string>>
     */
    public const ROLES = [
        User::PLATFORM_ADMIN_ROLE => ['platform.manage'],
        WorkspaceRole::Owner->value => ['workspace.view', 'workspace.manage', 'members.view', 'members.manage', 'knowledge.view', 'knowledge.manage'],
        WorkspaceRole::Admin->value => ['workspace.view', 'workspace.manage', 'members.view', 'members.manage', 'knowledge.view', 'knowledge.manage'],
        WorkspaceRole::Agent->value => ['workspace.view', 'members.view', 'knowledge.view', 'knowledge.manage'],
        WorkspaceRole::Viewer->value => ['workspace.view', 'members.view', 'knowledge.view'],
    ];

    public function run(): void
    {
        foreach (array_keys(self::PERMISSIONS) as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        foreach (self::ROLES as $roleName => $permissions) {
            Role::findOrCreate($roleName, self::GUARD)->syncPermissions($permissions);
        }

        // Le cache du paquet garde l'ancienne matrice : sans ce vidage, les
        // droits fraîchement semés ne s'appliquent qu'à la requête suivante.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
