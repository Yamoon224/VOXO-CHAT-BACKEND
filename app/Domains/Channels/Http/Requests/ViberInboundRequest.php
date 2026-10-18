<?php

namespace App\Domains\Channels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Forme simplifiée du webhook Viber : un seul message par appel. */
class ViberInboundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'sender.id' => ['required', 'string'],
            'message.text' => ['required', 'string'],
        ];
    }

    public function senderId(): string
    {
        return (string) $this->input('sender.id');
    }

    public function body(): string
    {
        return (string) $this->input('message.text');
    }

    public function senderName(): ?string
    {
        $name = $this->input('sender.name');

        return is_string($name) && $name !== '' ? $name : null;
    }
}
