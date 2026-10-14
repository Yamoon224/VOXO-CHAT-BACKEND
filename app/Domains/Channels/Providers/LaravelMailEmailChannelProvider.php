<?php

namespace App\Domains\Channels\Providers;

use App\Domains\Channels\Contracts\EmailChannelProviderContract;
use App\Domains\Channels\Support\ConversationReplyMail;
use Illuminate\Contracts\Mail\Mailer;

/** Pilote par défaut : passe par le mailer Laravel déjà configuré (SMTP, section 2.15), en file d'attente. */
final class LaravelMailEmailChannelProvider implements EmailChannelProviderContract
{
    public function __construct(private readonly Mailer $mailer) {}

    public function sendReply(string $toEmail, string $fromEmail, string $fromName, string $subject, string $body): void
    {
        $this->mailer->to($toEmail)->queue(new ConversationReplyMail($fromEmail, $fromName, $subject, $body));
    }
}
