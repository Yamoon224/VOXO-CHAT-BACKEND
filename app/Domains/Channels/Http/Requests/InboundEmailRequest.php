<?php

namespace App\Domains\Channels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InboundEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from_email' => ['required', 'email'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:1', 'max:20000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['from_email' => 'adresse e-mail', 'from_name' => 'nom', 'body' => 'message'];
    }
}
