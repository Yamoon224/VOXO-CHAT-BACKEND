<?php

namespace App\Domains\Channels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Forme Twilio-like (prestataire non tranché, section 12 du cahier des charges) : champs `From`/`Body` en formulaire. */
class SmsInboundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'From' => ['required', 'string'],
            'Body' => ['required', 'string'],
        ];
    }
}
