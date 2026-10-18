<?php

namespace App\Domains\StatusPage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeStatusAlertsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['email' => ['required', 'email', 'max:255']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['email' => 'e-mail'];
    }
}
