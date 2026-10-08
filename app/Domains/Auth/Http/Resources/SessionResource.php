<?php

namespace App\Domains\Auth\Http\Resources;

use App\Domains\Auth\DTOs\SessionView;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * La session telle que l'appelant se voit lui-même.
 *
 * @property-read SessionView $resource
 */
class SessionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $session = $this->resource;
        $user = $session->user;

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale,
                'email_verified' => $user->hasVerifiedEmail(),
                'two_factor_enabled' => $user->hasTwoFactorEnabled(),
                'is_platform_admin' => $session->isPlatformAdmin,
                'last_login_at' => $user->last_login_at?->toIso8601String(),
            ],
            'workspace' => $session->currentMembership === null
                ? null
                : self::workspace($session->currentMembership),
            'permissions' => $session->permissions,
            'workspaces' => $session->memberships
                ->map(fn (WorkspaceMember $membership) => self::workspace($membership))
                ->values()
                ->all(),
        ];
    }

    /** @return array{id: string, name: string, slug: string, role: string} */
    private static function workspace(WorkspaceMember $membership): array
    {
        return [
            'id' => $membership->workspace->id,
            'name' => $membership->workspace->name,
            'slug' => $membership->workspace->slug,
            'role' => $membership->role->value,
        ];
    }
}
