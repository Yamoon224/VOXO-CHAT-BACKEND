<?php

namespace App\Domains\Integrations\Http\Requests;

use App\Domains\Integrations\Enums\WebhookEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebhookSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
            'event_types' => ['required', 'array', 'min:1'],
            'event_types.*' => [Rule::in(WebhookEventType::values())],
            'enabled' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['url' => 'URL', 'event_types' => "types d'évènement"];
    }

    /** @return array{url: string, event_types: list<string>, enabled: bool} */
    public function toAttributes(): array
    {
        return [
            'url' => $this->string('url')->toString(),
            'event_types' => $this->input('event_types', []),
            'enabled' => $this->boolean('enabled', true),
        ];
    }
}
