<?php

namespace App\Domains\Conversations\Http\Requests;

use App\Domains\Conversations\Enums\ConversationChannel;
use App\Domains\Conversations\Enums\ConversationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInboxViewRequest extends FormRequest
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
            'filters' => ['sometimes', 'array'],
            'filters.status' => ['sometimes', 'nullable', Rule::enum(ConversationStatus::class)],
            'filters.assigned_user_id' => ['sometimes', 'nullable', 'string', 'uuid'],
            'filters.channel' => ['sometimes', 'nullable', Rule::enum(ConversationChannel::class)],
            'filters.needs_human' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nom'];
    }

    public function position(): int
    {
        return $this->integer('position', 0);
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return array_filter($this->input('filters', []), fn (mixed $value) => $value !== null);
    }
}
