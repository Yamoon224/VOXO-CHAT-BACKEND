<?php

namespace App\Domains\Conversations\Http\Requests;

use App\Domains\Conversations\Enums\ConversationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListConversationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', Rule::enum(ConversationStatus::class)],
            'assigned_user_id' => ['sometimes', 'nullable', 'string', 'uuid'],
            'needs_human' => ['sometimes', 'nullable', 'boolean'],
            'sort' => ['sometimes', 'nullable', 'string', 'max:40'],
            'direction' => ['sometimes', 'nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
