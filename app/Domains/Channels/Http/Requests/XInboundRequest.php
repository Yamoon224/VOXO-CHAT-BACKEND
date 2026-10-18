<?php

namespace App\Domains\Channels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Forme simplifiée du webhook X (Twitter) Account Activity API : un seul message direct par appel. */
class XInboundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'direct_message_events.0.message_create.sender_id' => ['required', 'string'],
            'direct_message_events.0.message_create.message_data.text' => ['required', 'string'],
        ];
    }

    public function senderId(): string
    {
        return (string) $this->input('direct_message_events.0.message_create.sender_id');
    }

    public function body(): string
    {
        return (string) $this->input('direct_message_events.0.message_create.message_data.text');
    }

    public function senderName(): ?string
    {
        $name = $this->input("users.{$this->senderId()}.name");

        return is_string($name) && $name !== '' ? $name : null;
    }
}
