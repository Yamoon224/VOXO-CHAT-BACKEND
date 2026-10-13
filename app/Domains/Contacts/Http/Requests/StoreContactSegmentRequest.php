<?php

namespace App\Domains\Contacts\Http\Requests;

use App\Domains\Contacts\Enums\SegmentOperator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactSegmentRequest extends FormRequest
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
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.field' => ['required', 'string', 'max:60'],
            'criteria.*.operator' => ['required', Rule::enum(SegmentOperator::class)],
            'criteria.*.value' => ['nullable'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nom', 'criteria' => 'critères'];
    }
}
