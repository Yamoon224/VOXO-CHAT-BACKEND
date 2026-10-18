<?php

namespace App\Domains\StatusPage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnsubscribeStatusAlertsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['token' => ['required', 'string']];
    }
}
