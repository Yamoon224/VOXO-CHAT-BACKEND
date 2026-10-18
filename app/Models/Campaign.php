<?php

namespace App\Models;

use App\Domains\Campaigns\Enums\CampaignChannel;
use App\Domains\Campaigns\Enums\CampaignStatus;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Campagne ponctuelle (section 2.9) : un modèle, un segment ciblé, un canal —
 * voir `CampaignDispatchService`. Les statistiques d'envoi vivent dans
 * `campaign_sends`, pas ici : elles continuent d'évoluer après `sent_at`.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $name
 * @property CampaignChannel $channel
 * @property string $segment_id
 * @property string $template_id
 * @property CampaignStatus $status
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int $pending_sends_count Chargé par `EloquentCampaignRepository::withSendCounts()`, jamais par défaut.
 * @property-read int $sent_sends_count
 * @property-read int $failed_sends_count
 * @property-read int $skipped_sends_count
 */
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'name', 'channel', 'segment_id', 'template_id', 'status', 'sent_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['channel' => CampaignChannel::class, 'status' => CampaignStatus::class, 'sent_at' => 'datetime'];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<ContactSegment, $this> */
    public function segment(): BelongsTo
    {
        return $this->belongsTo(ContactSegment::class);
    }

    /** @return BelongsTo<CampaignTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CampaignTemplate::class);
    }

    /** @return HasMany<CampaignSend, $this> */
    public function sends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }
}
