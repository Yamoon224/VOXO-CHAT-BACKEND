<?php

namespace App\Domains\Widget\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'conversation_id' => ['required', 'string', 'uuid'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
