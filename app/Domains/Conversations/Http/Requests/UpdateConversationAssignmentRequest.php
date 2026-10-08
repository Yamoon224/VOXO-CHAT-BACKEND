<?php

namespace App\Domains\Conversations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['user_id' => ['sometimes', 'nullable', 'string', 'uuid']];
    }

    public function userId(): ?string
    {
        return $this->string('user_id')->toString() ?: null;
    }
}
