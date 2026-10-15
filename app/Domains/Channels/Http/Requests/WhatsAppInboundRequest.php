<?php

namespace App\Domains\Channels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Forme simplifiée du webhook WhatsApp Cloud API : seul le premier message
 * du premier changement importe ici — Meta regroupe plusieurs évènements par
 * appel (statuts de livraison compris), hors périmètre de cette réception.
 */
class WhatsAppInboundRequest extends FormRequest
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
            'entry.0.changes.0.value.messages.0.from' => ['required', 'string'],
            'entry.0.changes.0.value.messages.0.text.body' => ['required', 'string'],
        ];
    }

    public function fromPhone(): string
    {
        return (string) $this->input('entry.0.changes.0.value.messages.0.from');
    }

    public function body(): string
    {
        return (string) $this->input('entry.0.changes.0.value.messages.0.text.body');
    }

    public function senderName(): ?string
    {
        $name = $this->input('entry.0.changes.0.value.contacts.0.profile.name');

        return is_string($name) && $name !== '' ? $name : null;
    }
}
