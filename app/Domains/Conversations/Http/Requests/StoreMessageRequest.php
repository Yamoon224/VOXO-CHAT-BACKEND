<?php

namespace App\Domains\Conversations\Http\Requests;

use App\Domains\Conversations\Enums\MessageVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:10000'],
            'visibility' => ['sometimes', 'nullable', Rule::enum(MessageVisibility::class)],
        ];
    }

    public function visibility(): MessageVisibility
    {
        $value = $this->string('visibility')->toString();

        return $value === '' ? MessageVisibility::Public : MessageVisibility::from($value);
    }
}
