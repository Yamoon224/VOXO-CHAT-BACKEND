<?php

namespace App\Domains\Tickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostTicketPortalMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'body' => ['required', 'string', 'min:1', 'max:10000'],
        ];
    }
}
