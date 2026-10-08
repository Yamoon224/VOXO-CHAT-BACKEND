<?php

namespace App\Domains\Workspaces\Http\Resources;

use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un membre vu depuis l'équipe de l'espace de travail.
 *
 * @mixin WorkspaceMember
 */
class MemberResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'joined_at' => $this->created_at?->toIso8601String(),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'last_login_at' => $this->user->last_login_at?->toIso8601String(),
            ],
        ];
    }
}
