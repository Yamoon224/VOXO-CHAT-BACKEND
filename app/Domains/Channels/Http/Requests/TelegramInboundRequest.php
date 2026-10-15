<?php

namespace App\Domains\Channels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Forme simplifiée du webhook Telegram (Bot API) : un seul message par appel. */
class TelegramInboundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'message.chat.id' => ['required'],
            'message.text' => ['required', 'string'],
        ];
    }

    public function chatId(): string
    {
        return (string) $this->input('message.chat.id');
    }

    public function body(): string
    {
        return (string) $this->input('message.text');
    }

    public function senderName(): ?string
    {
        $firstName = $this->input('message.from.first_name');
        $username = $this->input('message.from.username');
        $name = is_string($firstName) ? $firstName : $username;

        return is_string($name) && $name !== '' ? $name : null;
    }
}
