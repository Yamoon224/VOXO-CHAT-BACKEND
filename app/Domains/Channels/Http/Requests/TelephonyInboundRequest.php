<?php

namespace App\Domains\Channels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Forme Twilio-like (callback de statut d'appel vocal) : journal d'appel
 * uniquement, pas de téléphonie temps réel (voix/IVR) — voir
 * `TelephonyChannelProviderContract`.
 */
class TelephonyInboundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'CallSid' => ['required', 'string'],
            'From' => ['required', 'string'],
            'To' => ['required', 'string'],
            'CallStatus' => ['required', 'string'],
            'CallDuration' => ['nullable', 'string'],
        ];
    }

    /** Résumé de l'appel enregistré comme message de la conversation (aucune audio/transcription). */
    public function summary(): string
    {
        $status = $this->string('CallStatus')->toString();

        $label = match ($status) {
            'completed' => 'Appel terminé',
            'busy' => 'Appel occupé',
            'failed' => 'Appel échoué',
            'no-answer' => 'Appel sans réponse',
            'canceled' => 'Appel annulé',
            default => "Appel : {$status}",
        };

        $duration = $this->input('CallDuration');

        if (is_string($duration) && $duration !== '' && ctype_digit($duration) && (int) $duration > 0) {
            $seconds = (int) $duration;
            $label .= sprintf(' (durée %02d:%02d)', intdiv($seconds, 60), $seconds % 60);
        }

        return $label;
    }
}
