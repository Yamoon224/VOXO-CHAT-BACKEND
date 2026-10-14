<?php

namespace App\Domains\Tickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenPublicTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'name' => ['nullable', 'string', 'max:120'],
            'subject' => ['required', 'string', 'min:1', 'max:200'],
            'message' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['email' => 'adresse e-mail', 'name' => 'nom', 'subject' => 'sujet', 'message' => 'message'];
    }
}
