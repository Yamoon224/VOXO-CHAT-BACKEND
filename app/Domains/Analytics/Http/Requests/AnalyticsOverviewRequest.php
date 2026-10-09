<?php

namespace App\Domains\Analytics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnalyticsOverviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['from' => 'début de période', 'to' => 'fin de période'];
    }
}
