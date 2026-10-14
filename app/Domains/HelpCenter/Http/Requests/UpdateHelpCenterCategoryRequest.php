<?php

namespace App\Domains\HelpCenter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHelpCenterCategoryRequest extends FormRequest
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
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function position(): int
    {
        return $this->integer('position', 0);
    }
}
