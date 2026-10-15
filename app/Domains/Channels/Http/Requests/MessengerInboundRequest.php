<?php

namespace App\Domains\Channels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Forme simplifiée du webhook Messenger : seul le premier évènement du premier changement importe ici. */
class MessengerInboundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'entry' => ['required', 'array'],
            'entry.0.messaging.0.sender.id' => ['required', 'string'],
            'entry.0.messaging.0.message.text' => ['required', 'string'],
        ];
    }

    public function senderPsid(): string
    {
        return (string) $this->input('entry.0.messaging.0.sender.id');
    }

    public function body(): string
    {
        return (string) $this->input('entry.0.messaging.0.message.text');
    }
}
