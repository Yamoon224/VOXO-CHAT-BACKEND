<?php

namespace App\Domains\Campaigns\Services;

use App\Domains\Campaigns\Contracts\CampaignRepositoryContract;
use App\Domains\Campaigns\Contracts\CampaignSendRepositoryContract;
use App\Domains\Campaigns\Contracts\CampaignTemplateRepositoryContract;
use App\Domains\Campaigns\Enums\CampaignSendStatus;
use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Exceptions\CampaignAlreadySentException;
use App\Domains\Campaigns\Jobs\SendCampaignMessageJob;
use App\Domains\Contacts\Contracts\ContactSegmentMatcherContract;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Models\Campaign;
use App\Models\CampaignSend;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/** CRUD et déclenchement des campagnes ponctuelles (section 2.9) — l'envoi lui-même est dans `SendCampaignMessageJob`. */
final class CampaignService
{
    public function __construct(
        private readonly CampaignRepositoryContract $campaigns,
        private readonly CampaignSendRepositoryContract $sends,
        private readonly CampaignTemplateRepositoryContract $templates,
        private readonly ContactSegmentMatcherContract $segments,
    ) {}

    /** @return Collection<int, Campaign> */
    public function list(WorkspaceScope $scope): Collection
    {
        return $this->campaigns->forWorkspace($scope->workspaceId);
    }

    public function get(WorkspaceScope $scope, string $campaignId): Campaign
    {
        return $this->campaigns->findInWorkspaceOrFail($scope->workspaceId, $campaignId);
    }

    /** @return Collection<int, CampaignSend> */
    public function sends(WorkspaceScope $scope, string $campaignId): Collection
    {
        $this->campaigns->findInWorkspaceOrFail($scope->workspaceId, $campaignId);

        return $this->sends->forCampaign($campaignId);
    }

    /**
     * @param  array{name: string, channel: string, segment_id: string, template_id: string}  $attributes
     *
     * @throws ModelNotFoundException Segment ou modèle introuvable dans cet espace.
     */
    public function create(WorkspaceScope $scope, array $attributes): Campaign
    {
        $this->segments->assertExistsInWorkspace($scope->workspaceId, $attributes['segment_id']);
        $this->templates->findInWorkspaceOrFail($scope->workspaceId, $attributes['template_id']);

        return $this->campaigns->create([...$attributes, 'workspace_id' => $scope->workspaceId, 'status' => CampaignStatus::Draft]);
    }

    /**
     * @param  array{name: string, channel: string, segment_id: string, template_id: string}  $attributes
     *
     * @throws CampaignAlreadySentException
     * @throws ModelNotFoundException Segment ou modèle introuvable dans cet espace.
     */
    public function update(WorkspaceScope $scope, string $campaignId, array $attributes): Campaign
    {
        $campaign = $this->campaigns->findInWorkspaceOrFail($scope->workspaceId, $campaignId);

        if ($campaign->status === CampaignStatus::Sent) {
            throw CampaignAlreadySentException::make();
        }

        $this->segments->assertExistsInWorkspace($scope->workspaceId, $attributes['segment_id']);
        $this->templates->findInWorkspaceOrFail($scope->workspaceId, $attributes['template_id']);

        return $this->campaigns->update($campaign, $attributes);
    }

    public function delete(WorkspaceScope $scope, string $campaignId): void
    {
        $this->campaigns->delete($this->campaigns->findInWorkspaceOrFail($scope->workspaceId, $campaignId));
    }

    /**
     * Résout le segment ciblé, enregistre une tentative par contact, et met
     * chaque envoi en file : jamais dans la même requête, pour qu'un grand
     * segment ne fasse pas attendre qui déclenche l'envoi.
     *
     * @throws CampaignAlreadySentException
     */
    public function send(WorkspaceScope $scope, string $campaignId): Campaign
    {
        $campaign = $this->campaigns->findInWorkspaceOrFail($scope->workspaceId, $campaignId);

        if ($campaign->status === CampaignStatus::Sent) {
            throw CampaignAlreadySentException::make();
        }

        $contacts = $this->segments->matching($scope->workspaceId, $campaign->segment_id);

        foreach ($contacts as $contact) {
            $send = $this->sends->create([
                'workspace_id' => $scope->workspaceId,
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->id,
                'status' => CampaignSendStatus::Pending,
            ]);

            SendCampaignMessageJob::dispatch($campaign->id, $send->id);
        }

        return $this->campaigns->update($campaign, ['status' => CampaignStatus::Sent, 'sent_at' => now()]);
    }
}
