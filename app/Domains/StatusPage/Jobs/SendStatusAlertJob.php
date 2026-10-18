<?php

namespace App\Domains\StatusPage\Jobs;

use App\Domains\Notifications\Contracts\MailSenderContract;
use App\Domains\Shared\Support\FrontendUrl;
use App\Domains\StatusPage\Contracts\StatusSubscriberRepositoryContract;
use App\Domains\StatusPage\Support\StatusSubscriberToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Livre une alerte à un abonné — en file, même raison que `DeliverWebhookJob`/`SendCampaignMessageJob`. */
final class SendStatusAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly string $subscriberId,
        public readonly string $incidentTitle,
        public readonly string $statusLabel,
        public readonly string $body,
    ) {}

    public function handle(
        StatusSubscriberRepositoryContract $subscribers,
        StatusSubscriberToken $token,
        FrontendUrl $frontendUrl,
        MailSenderContract $mailer,
    ): void {
        $subscriber = $subscribers->findById($this->subscriberId);

        if ($subscriber === null) {
            return;
        }

        $unsubscribeUrl = $frontendUrl->to('public/status/unsubscribe', [
            'token' => $token->issue($subscriber->workspace_id, $subscriber->id),
        ]);

        $bodyHtml = sprintf(
            '<p><strong>%s</strong></p><p>%s</p><p><a href="%s">Se désabonner</a></p>',
            e($this->statusLabel),
            e($this->body),
            e($unsubscribeUrl),
        );

        $mailer->sendRaw($subscriber->email, "Mise à jour : {$this->incidentTitle}", $bodyHtml);
    }
}
