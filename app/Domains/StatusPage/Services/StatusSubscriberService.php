<?php

namespace App\Domains\StatusPage\Services;

use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\StatusPage\Contracts\StatusSubscriberRepositoryContract;
use App\Domains\StatusPage\Support\StatusSubscriberToken;
use App\Models\StatusSubscriber;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/** Abonnement aux alertes (section 2.11) : inscription publique à e-mail simple, désinscription par lien signé. */
final class StatusSubscriberService
{
    public function __construct(
        private readonly StatusSubscriberRepositoryContract $subscribers,
        private readonly StatusSubscriberToken $token,
    ) {}

    /** @return Collection<int, StatusSubscriber> */
    public function list(WorkspaceScope $scope): Collection
    {
        return $this->subscribers->forWorkspace($scope->workspaceId);
    }

    public function remove(WorkspaceScope $scope, string $subscriberId): void
    {
        $subscriber = $this->subscribers->findInWorkspace($scope->workspaceId, $subscriberId);

        if ($subscriber === null) {
            throw new ModelNotFoundException;
        }

        $this->subscribers->delete($subscriber);
    }

    /** Déjà abonnée : pas d'erreur, pas de doublon — un visiteur qui s'inscrit deux fois ne doit rien remarquer. */
    public function subscribe(string $workspaceId, string $email): StatusSubscriber
    {
        return $this->subscribers->findByEmail($workspaceId, $email)
            ?? $this->subscribers->create(['workspace_id' => $workspaceId, 'email' => $email]);
    }

    /** `true` si le jeton était valide et l'abonné a été retiré ; `false` sinon, sans jamais révéler pourquoi. */
    public function unsubscribe(string $rawToken): bool
    {
        $claims = $this->token->verify($rawToken);

        if ($claims === null) {
            return false;
        }

        $subscriber = $this->subscribers->findInWorkspace($claims['workspaceId'], $claims['subscriberId']);

        if ($subscriber === null) {
            return false;
        }

        $this->subscribers->delete($subscriber);

        return true;
    }
}
