<?php

namespace App\Domains\Knowledge\Http\Requests;

use App\Domains\Knowledge\Enums\KnowledgeDocumentStatus;
use App\Domains\Knowledge\Enums\KnowledgeDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListKnowledgeDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'source_id' => ['sometimes', 'nullable', 'string', 'uuid'],
            'status' => ['sometimes', 'nullable', Rule::enum(KnowledgeDocumentStatus::class)],
            'type' => ['sometimes', 'nullable', Rule::enum(KnowledgeDocumentType::class)],
            'sort' => ['sometimes', 'nullable', 'string', 'max:40'],
            'direction' => ['sometimes', 'nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
