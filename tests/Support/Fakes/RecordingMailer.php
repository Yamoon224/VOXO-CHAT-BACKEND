<?php

namespace Tests\Support\Fakes;

use App\Domains\Notifications\Contracts\TransactionalMailerContract;

final class RecordingMailer implements TransactionalMailerContract
{
    /** @var list<array{type: string, email: string, token: string, data: array<string, string>}> */
    public array $sent = [];

    public function sendEmailVerification(string $email, string $name, string $token): void
    {
        $this->sent[] = ['type' => 'email_verification', 'email' => $email, 'token' => $token, 'data' => ['name' => $name]];
    }

    public function sendPasswordReset(string $email, string $name, string $token): void
    {
        $this->sent[] = ['type' => 'password_reset', 'email' => $email, 'token' => $token, 'data' => ['name' => $name]];
    }

    public function sendWorkspaceInvitation(
        string $email,
        string $workspaceName,
        string $inviterName,
        string $roleLabel,
        string $token,
    ): void {
        $this->sent[] = ['type' => 'workspace_invitation', 'email' => $email, 'token' => $token, 'data' => [
            'workspace_name' => $workspaceName,
            'inviter_name' => $inviterName,
            'role_label' => $roleLabel,
        ]];
    }

    public function lastToken(): ?string
    {
        return $this->sent === [] ? null : $this->sent[array_key_last($this->sent)]['token'];
    }
}
