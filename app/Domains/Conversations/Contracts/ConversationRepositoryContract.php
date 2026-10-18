<?php

namespace App\Domains\Conversations\Contracts;

use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ConversationRepositoryContract
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Conversation>
     */
    public function paginate(string $workspaceId, array $filters, int $perPage): LengthAwarePaginator;

    public function findInWorkspaceOrFail(string $workspaceId, string $id): Conversation;

    /**
     * Chargement sans périmètre, pour la chaîne de réponse de l'agent IA en
     * file : le job ne porte pas de jeton d'appelant, seul l'identifiant
     * généré à la création de la conversation (jamais fourni par un visiteur).
     */
    public function findOrFailById(string $id): Conversation;

    public function findOpenForVisitor(string $workspaceId, string $visitorId): ?Conversation;

    /** Tous statuts confondus : pour afficher/sonder le fil, après résolution comprise. */
    public function findLatestForVisitor(string $workspaceId, string $visitorId): ?Conversation;

    /**
     * Même mécanique que `findLatestForVisitor()`, mais par profil CRM plutôt
     * que par identifiant de canal — pour un domaine qui ne connaît un client
     * que par son contact, jamais par sa session de visiteur (`Campaigns`,
     * lot 6, section 2.9 : relancer une conversation existante côté chat).
     */
    public function findLatestForContact(string $workspaceId, string $contactId): ?Conversation;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): Conversation;

    /** @param  array<string, mixed>  $attributes */
    public function update(Conversation $conversation, array $attributes): Conversation;

    /**
     * L'agent IA est descendu sous le seuil de confiance : encapsule le
     * statut et le drapeau d'escalade, pour que les domaines appelants
     * (`Assistant`) n'aient pas à connaître l'enum de statut.
     */
    public function escalateToHuman(Conversation $conversation): Conversation;
}
