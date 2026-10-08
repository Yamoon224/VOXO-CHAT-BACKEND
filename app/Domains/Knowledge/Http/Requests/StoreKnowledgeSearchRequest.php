<?php

namespace App\Domains\Knowledge\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKnowledgeSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:2', 'max:500'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function limit(): int
    {
        return (int) ($this->input('limit') ?? 5);
    }
}
