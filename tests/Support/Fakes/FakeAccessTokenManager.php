<?php

namespace Tests\Support\Fakes;

use App\Domains\Auth\Contracts\AccessTokenManagerContract;
use App\Models\User;

final class FakeAccessTokenManager implements AccessTokenManagerContract
{
    /** @var list<array{user_id: string, workspace_id: string|null, device: string}> */
    public array $issued = [];

    /** @var list<string> */
    public array $revokedAllFor = [];

    public bool $currentRevoked = false;

    public function __construct(public ?string $currentWorkspaceId = null) {}

    public function issue(User $user, ?string $workspaceId, string $deviceName): string
    {
        $this->issued[] = ['user_id' => $user->id, 'workspace_id' => $workspaceId, 'device' => $deviceName];

        return 'token-'.count($this->issued);
    }

    public function currentWorkspaceId(User $user): ?string
    {
        return $this->currentWorkspaceId;
    }

    public function bindCurrentToWorkspace(User $user, string $workspaceId): void
    {
        $this->currentWorkspaceId = $workspaceId;
    }

    public function revokeCurrent(User $user): void
    {
        $this->currentRevoked = true;
    }

    public function revokeAll(User $user): void
    {
        $this->revokedAllFor[] = $user->id;
    }
}
