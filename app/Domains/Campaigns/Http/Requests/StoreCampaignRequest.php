<?php

namespace App\Domains\Campaigns\Http\Requests;

use App\Domains\Campaigns\Enums\CampaignChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'channel' => ['required', Rule::in(CampaignChannel::values())],
            'segment_id' => ['required', 'string', 'uuid'],
            'template_id' => ['required', 'string', 'uuid'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nom', 'channel' => 'canal', 'segment_id' => 'segment', 'template_id' => 'modèle'];
    }

    /** @return array{name: string, channel: string, segment_id: string, template_id: string} */
    public function toAttributes(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'channel' => $this->string('channel')->toString(),
            'segment_id' => $this->string('segment_id')->toString(),
            'template_id' => $this->string('template_id')->toString(),
        ];
    }
}
