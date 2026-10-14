<?php

namespace App\Domains\HelpCenter\Http\Requests;

use App\Domains\HelpCenter\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHelpCenterArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'category_id' => ['sometimes', 'nullable', 'string', 'uuid'],
            'body' => ['required', 'string'],
            'status' => ['sometimes', Rule::enum(ArticleStatus::class)],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:160'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /** @return array{title: string, category_id: ?string, body: string, status: ArticleStatus, meta_description: ?string, position: int} */
    public function toAttributes(): array
    {
        $status = $this->string('status')->toString();

        return [
            'title' => $this->string('title')->toString(),
            'category_id' => $this->string('category_id')->toString() ?: null,
            'body' => $this->string('body')->toString(),
            'status' => $status === '' ? ArticleStatus::Draft : ArticleStatus::from($status),
            'meta_description' => $this->string('meta_description')->toString() ?: null,
            'position' => $this->integer('position', 0),
        ];
    }
}
