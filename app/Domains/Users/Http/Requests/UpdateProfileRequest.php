<?php

namespace App\Domains\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public const LOCALES = ['fr', 'en'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'locale' => ['sometimes', 'required', Rule::in(self::LOCALES)],
        ];
    }

    /** @return array{name?: string, locale?: string} */
    public function profileAttributes(): array
    {
        /** @var array{name?: string, locale?: string} $attributes */
        $attributes = $this->safe()->only(['name', 'locale']);

        return $attributes;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nom', 'locale' => 'langue'];
    }
}
