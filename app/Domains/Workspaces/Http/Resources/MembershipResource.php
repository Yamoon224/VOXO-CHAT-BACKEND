<?php

namespace App\Domains\Workspaces\Http\Resources;

use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un espace de travail vu depuis le compte qui en est membre : l'espace, et
 * le rôle que l'appelant y tient.
 *
 * @mixin WorkspaceMember
 */
class MembershipResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->workspace->id,
            'name' => $this->workspace->name,
            'slug' => $this->workspace->slug,
            'role' => $this->role->value,
        ];
    }
}
