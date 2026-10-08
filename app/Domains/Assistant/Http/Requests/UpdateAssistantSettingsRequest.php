<?php

namespace App\Domains\Assistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssistantSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'enabled' => ['sometimes', 'boolean'],
            'tone_instructions' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'confidence_threshold' => ['sometimes', 'numeric', 'min:0', 'max:1'],
        ];
    }
}
