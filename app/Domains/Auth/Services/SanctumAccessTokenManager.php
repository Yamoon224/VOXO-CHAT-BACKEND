<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\AccessTokenManagerContract;
use App\Models\PersonalAccessToken;
use App\Models\User;

final class SanctumAccessTokenManager implements AccessTokenManagerContract
{
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

    /** `null` hors d'une requête authentifiée par jeton personnel. */
    private function current(User $user): ?PersonalAccessToken
    {
        $token = $user->currentAccessToken();

        return $token instanceof PersonalAccessToken ? $token : null;
    }
}
