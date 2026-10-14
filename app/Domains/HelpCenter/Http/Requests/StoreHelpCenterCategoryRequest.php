<?php

namespace App\Domains\HelpCenter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHelpCenterCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'locale' => ['sometimes', 'string', Rule::in(['fr', 'en'])],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function locale(): string
    {
        $value = $this->string('locale')->toString();

        return $value === '' ? 'fr' : $value;
    }

    public function position(): int
    {
        return $this->integer('position', 0);
    }
}
