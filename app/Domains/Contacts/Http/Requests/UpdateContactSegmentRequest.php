<?php

namespace App\Domains\Contacts\Http\Requests;

use App\Domains\Contacts\Enums\SegmentOperator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'criteria' => ['sometimes', 'required', 'array', 'min:1'],
            'criteria.*.field' => ['required_with:criteria', 'string', 'max:60'],
            'criteria.*.operator' => ['required_with:criteria', Rule::enum(SegmentOperator::class)],
            'criteria.*.value' => ['nullable'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nom', 'criteria' => 'critères'];
    }
}
