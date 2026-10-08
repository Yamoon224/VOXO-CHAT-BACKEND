<?php

namespace App\Domains\Workspaces\Http\Resources;

use App\Models\WorkspaceInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * L'invitation telle que la voit l'invité avant de l'accepter.
 *
 * @mixin WorkspaceInvitation
 */
class InvitationPreviewResource extends JsonResource
{
    private bool $accountExists = false;

    public function withAccountExists(bool $accountExists): static
    {
        $this->accountExists = $accountExists;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'email' => $this->email,
            'workspace_name' => $this->workspace->name,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'invited_by' => $this->inviter?->name,
            'expires_at' => $this->expires_at->toIso8601String(),
            // Décide du formulaire : créer un compte, ou se connecter.
            'account_exists' => $this->accountExists,
        ];
    }
}
