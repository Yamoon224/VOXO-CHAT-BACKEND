<?php

namespace App\Domains\Widget\Http\Requests;

use App\Domains\Widget\Enums\WidgetPosition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWidgetSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'primary_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'position' => ['sometimes', Rule::enum(WidgetPosition::class)],
            'welcome_message' => ['sometimes', 'nullable', 'string', 'max:500'],
            'language' => ['sometimes', 'string', Rule::in(['fr', 'en'])],
            'business_hours' => ['sometimes', 'nullable', 'array'],
            'business_hours.*.day' => ['required_with:business_hours', 'integer', 'min:0', 'max:6'],
            'business_hours.*.opens_at' => ['required_with:business_hours', 'date_format:H:i'],
            'business_hours.*.closes_at' => ['required_with:business_hours', 'date_format:H:i'],
            'offline_message' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
