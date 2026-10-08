<?php

namespace App\Domains\Knowledge\Http\Requests;

use App\Domains\Knowledge\Enums\KnowledgeSourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListKnowledgeSourcesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'nullable', Rule::enum(KnowledgeSourceType::class)],
            'sort' => ['sometimes', 'nullable', 'string', 'max:40'],
            'direction' => ['sometimes', 'nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
