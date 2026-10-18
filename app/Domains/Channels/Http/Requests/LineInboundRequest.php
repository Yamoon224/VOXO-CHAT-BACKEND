<?php

namespace App\Domains\Channels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Forme simplifiée du webhook LINE Messaging API : un envoi peut porter
 * plusieurs évènements, seul le premier évènement `message` est traité (voir
 * `LineInboundController`) — pas de nom d'expéditeur dans l'évènement
 * lui-même (nécessiterait un appel séparé à l'API « Get profile »).
 */
class LineInboundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'events.0.source.userId' => ['required', 'string'],
            'events.0.message.text' => ['required', 'string'],
        ];
    }

    public function senderId(): string
    {
        return (string) $this->input('events.0.source.userId');
    }

    public function body(): string
    {
        return (string) $this->input('events.0.message.text');
    }
}
