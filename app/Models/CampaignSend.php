<?php

namespace App\Models;

use App\Domains\Campaigns\Enums\CampaignSendStatus;
use Database\Factories\CampaignSendFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Tentative d'envoi d'une campagne à un contact (section 2.9) : une ligne par
 * couple (campagne, contact), jamais réécrite au-delà de son issue finale.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $campaign_id
 * @property string $contact_id
 * @property CampaignSendStatus $status
 * @property string|null $error
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 */
class CampaignSend extends Model
{
    /** @use HasFactory<CampaignSendFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['workspace_id', 'campaign_id', 'contact_id', 'status', 'error', 'sent_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => CampaignSendStatus::class, 'sent_at' => 'datetime'];
    }

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
