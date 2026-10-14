<?php

namespace App\Domains\Tickets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestTicketPortalLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['email' => ['required', 'email']];
    }
}
