<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Contracts\MailSenderContract;
use App\Domains\Notifications\Contracts\TransactionalMailerContract;
use App\Domains\Notifications\DTOs\MailMessage;
use App\Domains\Notifications\Enums\MailTemplate;
use App\Domains\Shared\Support\FrontendUrl;

final class TransactionalMailer implements TransactionalMailerContract
{
    public function __construct(
        private readonly MailSenderContract $sender,
        private readonly FrontendUrl $frontendUrl,
    ) {}

    public function sendEmailVerification(string $email, string $name, string $token): void
    {
        $this->sender->send(new MailMessage($email, MailTemplate::EmailVerification, [
            'name' => $name,
            'action_url' => $this->frontendUrl->to('verify-email', ['token' => $token]),
        ]));
    }

    public function sendPasswordReset(string $email, string $name, string $token): void
    {
        $this->sender->send(new MailMessage($email, MailTemplate::PasswordReset, [
            'name' => $name,
            'action_url' => $this->frontendUrl->to('reset-password', ['token' => $token, 'email' => $email]),
        ]));
    }

    public function sendWorkspaceInvitation(
        string $email,
        string $workspaceName,
        string $inviterName,
        string $roleLabel,
        string $token,
    ): void {
        $this->sender->send(new MailMessage($email, MailTemplate::WorkspaceInvitation, [
            'workspace_name' => $workspaceName,
            'inviter_name' => $inviterName,
            'role_label' => $roleLabel,
            'action_url' => $this->frontendUrl->to('accept-invitation', ['token' => $token]),
        ]));
    }

    public function sendQuotaAlert(
        string $email,
        string $workspaceName,
        string $quotaLabel,
        int $thresholdPercent,
    ): void {
        $this->sender->send(new MailMessage($email, MailTemplate::QuotaAlert, [
            'workspace_name' => $workspaceName,
            'quota_label' => $quotaLabel,
            'threshold_percent' => $thresholdPercent,
            'action_url' => $this->frontendUrl->to('settings/billing'),
        ]));
    }

    public function sendTicketPortalLink(string $email, string $name, string $subject, string $token): void
    {
        $this->sender->send(new MailMessage($email, MailTemplate::TicketPortalLink, [
            'name' => $name,
            'subject' => $subject,
            'action_url' => $this->frontendUrl->to('portal/tickets', ['token' => $token]),
        ]));
    }
}
