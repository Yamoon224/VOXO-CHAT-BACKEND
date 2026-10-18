<?php

namespace App\Domains\Campaigns\Jobs;

use App\Domains\Campaigns\Contracts\CampaignRepositoryContract;
use App\Domains\Campaigns\Contracts\CampaignSendRepositoryContract;
use App\Domains\Campaigns\Enums\CampaignChannel;
use App\Domains\Campaigns\Enums\CampaignSendStatus;
use App\Domains\Conversations\Contracts\ConversationRepositoryContract;
use App\Domains\Conversations\Contracts\MessageRepositoryContract;
use App\Domains\Notifications\Contracts\MailSenderContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Livre une campagne à un contact, en file pour qu'un grand segment ne
 * ralentisse jamais la requête qui a déclenché l'envoi (même raison que
 * `DeliverWebhookJob`). Une tentative par contact, jamais rejouée au-delà de
 * son issue (`CampaignSend` porte l'unicité en base).
 */
final class SendCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly string $campaignId,
        public readonly string $sendId,
    ) {}

    public function handle(
        CampaignRepositoryContract $campaigns,
        CampaignSendRepositoryContract $sends,
        ConversationRepositoryContract $conversations,
        MessageRepositoryContract $messages,
        MailSenderContract $mailer,
    ): void {
        $campaign = $campaigns->findById($this->campaignId);
        $send = $sends->findById($this->sendId);

        if ($campaign === null || $send === null) {
            return;
        }

        $contact = $send->contact;
        $template = $campaign->template;
        $variables = ['name' => $contact->name ?? '', 'email' => $contact->email ?? ''];

        if ($campaign->channel === CampaignChannel::Email) {
            if ($contact->email === null) {
                $sends->update($send, ['status' => CampaignSendStatus::Skipped, 'error' => "Le contact n'a pas d'adresse e-mail."]);

                return;
            }

            $mailer->sendRaw($contact->email, $template->renderSubject($variables) ?? $campaign->name, $template->renderBodyForEmail($variables));
            $sends->update($send, ['status' => CampaignSendStatus::Sent, 'sent_at' => now()]);

            return;
        }

        $conversation = $conversations->findLatestForContact($campaign->workspace_id, $contact->id);

        if ($conversation === null) {
            $sends->update($send, ['status' => CampaignSendStatus::Skipped, 'error' => 'Aucune conversation existante pour ce contact.']);

            return;
        }

        $messages->recordSystemNotice($conversation->id, $campaign->workspace_id, $template->renderBody($variables));
        $sends->update($send, ['status' => CampaignSendStatus::Sent, 'sent_at' => now()]);
    }
}
