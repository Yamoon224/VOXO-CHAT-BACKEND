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
        'conversations.view' => 'Consulter les conversations de la boîte de réception.',
        'conversations.manage' => 'Répondre, affecter, changer le statut et noter les conversations.',
        'canned_responses.manage' => 'Créer, modifier et supprimer les réponses pré-enregistrées.',
        'widget.manage' => 'Régler l\'apparence, les horaires et le script du widget.',
        'assistant.manage' => "Régler l'agent IA et tester le bac à sable.",
        'billing.view' => "Consulter le palier, l'abonnement et la consommation de l'espace de travail.",
        'billing.manage' => "Changer de palier, résilier l'abonnement et consulter les factures.",
        'analytics.view' => "Consulter les statistiques d'activité de l'espace de travail.",
    ];

    /**
     * Rôle => permissions.
     *
     * @var array<string, list<string>>
     */
    public const ROLES = [
        User::PLATFORM_ADMIN_ROLE => ['platform.manage'],
        WorkspaceRole::Owner->value => [
            'workspace.view', 'workspace.manage', 'members.view', 'members.manage', 'knowledge.view', 'knowledge.manage',
            'conversations.view', 'conversations.manage', 'canned_responses.manage', 'widget.manage', 'assistant.manage',
            'billing.view', 'billing.manage', 'analytics.view',
        ],
        WorkspaceRole::Admin->value => [
            'workspace.view', 'workspace.manage', 'members.view', 'members.manage', 'knowledge.view', 'knowledge.manage',
            'conversations.view', 'conversations.manage', 'canned_responses.manage', 'widget.manage', 'assistant.manage',
            'billing.view', 'billing.manage', 'analytics.view',
        ],
        WorkspaceRole::Agent->value => [
            'workspace.view', 'members.view', 'knowledge.view', 'knowledge.manage',
            'conversations.view', 'conversations.manage', 'canned_responses.manage', 'analytics.view',
        ],
        WorkspaceRole::Viewer->value => ['workspace.view', 'members.view', 'knowledge.view', 'conversations.view', 'analytics.view'],
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
