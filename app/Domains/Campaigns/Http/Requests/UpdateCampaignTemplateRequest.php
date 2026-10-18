<?php

namespace App\Domains\Campaigns\Http\Requests;

use App\Domains\Campaigns\Enums\CampaignChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignTemplateRequest extends FormRequest
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
            'subject' => ['required_if:channel,email', 'nullable', 'string', 'max:200'],
            'body' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nom', 'channel' => 'canal', 'subject' => 'objet', 'body' => 'corps'];
    }

    /** @return array{name: string, channel: string, subject: ?string, body: string} */
    public function toAttributes(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'channel' => $this->string('channel')->toString(),
            'subject' => $this->filled('subject') ? $this->string('subject')->toString() : null,
            'body' => $this->string('body')->toString(),
        ];
    }
}
