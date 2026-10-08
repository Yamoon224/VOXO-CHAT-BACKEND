<?php

namespace App\Domains\Conversations\Http\Requests;

use App\Domains\Conversations\Enums\ConversationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConversationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(ConversationStatus::class)]];
    }

    public function status(): ConversationStatus
    {
        return ConversationStatus::from($this->string('status')->toString());
    }
}
