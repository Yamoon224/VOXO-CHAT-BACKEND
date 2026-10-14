<?php

namespace App\Domains\HelpCenter\Http\Requests;

use App\Domains\HelpCenter\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListHelpCenterArticlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', Rule::enum(ArticleStatus::class)],
            'category_id' => ['sometimes', 'nullable', 'string', 'uuid'],
            'locale' => ['sometimes', 'nullable', Rule::in(['fr', 'en'])],
        ];
    }
}
