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

    public function sendQuotaAlert(
        string $email,
        string $workspaceName,
        string $quotaLabel,
        int $thresholdPercent,
    ): void {
        $this->sent[] = ['type' => 'quota_alert', 'email' => $email, 'token' => '', 'data' => [
            'workspace_name' => $workspaceName,
            'quota_label' => $quotaLabel,
            'threshold_percent' => (string) $thresholdPercent,
        ]];
    }

    public function sendTicketPortalLink(string $email, string $name, string $subject, string $token): void
    {
        $this->sent[] = ['type' => 'ticket_portal_link', 'email' => $email, 'token' => $token, 'data' => [
            'name' => $name,
            'subject' => $subject,
        ]];
    }

    public function lastToken(): ?string
    {
        return $this->sent === [] ? null : $this->sent[array_key_last($this->sent)]['token'];
    }
}
