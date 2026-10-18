<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\AccessTokenManagerContract;
use App\Domains\Auth\Contracts\ApiKeyIssuerContract;
use App\Domains\Auth\DTOs\ApiKeyInfo;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class SanctumAccessTokenManager implements AccessTokenManagerContract, ApiKeyIssuerContract
{
    /** Marque un jeton comme clé d'API plutôt que session de connexion — jamais `['*']`, la forme des sessions. */
    private const API_KEY_ABILITY = 'api-key';

    public function issue(User $user, ?string $workspaceId, string $deviceName): string
    {
        $newToken = $user->createToken($deviceName);

        if ($workspaceId !== null) {
            $newToken->accessToken->forceFill(['workspace_id' => $workspaceId])->save();
        }

        return $newToken->plainTextToken;
    }

    public function currentWorkspaceId(User $user): ?string
    {
        return $this->current($user)?->workspace_id;
    }

    public function bindCurrentToWorkspace(User $user, string $workspaceId): void
    {
        $this->current($user)?->forceFill(['workspace_id' => $workspaceId])->save();
    }

    public function revokeCurrent(User $user): void
    {
        $this->current($user)?->delete();
    }

    public function revokeAll(User $user): void
    {
        $user->tokens()->delete();
    }

    public function issueApiKey(User $createdBy, string $workspaceId, string $name): string
    {
        $newToken = $createdBy->createToken($name, [self::API_KEY_ABILITY]);
        $newToken->accessToken->forceFill(['workspace_id' => $workspaceId])->save();

        return $newToken->plainTextToken;
    }

    /** @return Collection<int, ApiKeyInfo> */
    public function listForWorkspace(string $workspaceId): Collection
    {
        return $this->apiKeysQuery($workspaceId)
            ->with('tokenable')
            ->latest('created_at')
            ->get()
            ->map(fn (PersonalAccessToken $token) => new ApiKeyInfo(
                id: $token->id,
                name: $token->name,
                createdByName: $token->tokenable instanceof User ? $token->tokenable->name : 'Compte supprimé',
                lastUsedAt: $token->last_used_at === null ? null : Carbon::instance($token->last_used_at),
                createdAt: Carbon::instance($token->created_at),
            ));
    }

    public function revoke(string $workspaceId, string $tokenId): void
    {
        $token = $this->apiKeysQuery($workspaceId)->find($tokenId);

        if ($token === null) {
            throw new ModelNotFoundException;
        }

        $token->delete();
    }

    /** @return Builder<PersonalAccessToken> */
    private function apiKeysQuery(string $workspaceId): Builder
    {
        return PersonalAccessToken::query()
            ->where('workspace_id', $workspaceId)
            ->whereJsonContains('abilities', self::API_KEY_ABILITY);
    }

    /** `null` hors d'une requête authentifiée par jeton personnel. */
    private function current(User $user): ?PersonalAccessToken
    {
        $token = $user->currentAccessToken();

        return $token instanceof PersonalAccessToken ? $token : null;
    }
}
