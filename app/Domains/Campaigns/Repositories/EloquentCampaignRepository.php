<?php

namespace App\Domains\Campaigns\Repositories;

use App\Domains\Campaigns\Contracts\CampaignRepositoryContract;
use App\Models\Campaign;
use App\Models\CampaignSend;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentCampaignRepository implements CampaignRepositoryContract
{
    public function forWorkspace(string $workspaceId): Collection
    {
        return $this->withSendCounts()->where('workspace_id', $workspaceId)->latest('created_at')->get();
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $id): Campaign
    {
        return $this->withSendCounts()->where('workspace_id', $workspaceId)->findOrFail($id);
    }

    public function findById(string $id): ?Campaign
    {
        return Campaign::query()->find($id);
    }

    /**
     * Comptes d'envoi par statut (`CampaignSendStatus`), toujours chargés
     * avec la campagne : la vue liste autant que la vue détail les affiche.
     *
     * @return Builder<Campaign>
     */
    private function withSendCounts(): Builder
    {
        return Campaign::query()->withCount($this->sendCountClosures());
    }

    /** @return array<string, \Closure(Builder<CampaignSend>): Builder<CampaignSend>> */
    private function sendCountClosures(): array
    {
        return [
            'sends as pending_sends_count' => fn (Builder $query) => $query->where('status', 'pending'),
            'sends as sent_sends_count' => fn (Builder $query) => $query->where('status', 'sent'),
            'sends as failed_sends_count' => fn (Builder $query) => $query->where('status', 'failed'),
            'sends as skipped_sends_count' => fn (Builder $query) => $query->where('status', 'skipped'),
        ];
    }

    public function create(array $attributes): Campaign
    {
        $campaign = Campaign::query()->create($attributes);
        $campaign->loadCount($this->sendCountClosures());

        return $campaign;
    }

    public function update(Campaign $campaign, array $attributes): Campaign
    {
        $campaign->update($attributes);
        $campaign->loadCount($this->sendCountClosures());

        return $campaign;
    }

    public function delete(Campaign $campaign): void
    {
        $campaign->delete();
    }
}
