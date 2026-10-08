<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Contracts\MailSenderContract;
use App\Domains\Notifications\Contracts\TransactionalMailerContract;
use App\Domains\Notifications\DTOs\MailMessage;
use App\Domains\Notifications\Enums\MailTemplate;
use App\Domains\Notifications\Support\FrontendUrl;

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
}
