<?php

namespace App\Domains\Tickets\Http\Requests;

use App\Domains\Tickets\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenTicketFromConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'priority' => ['sometimes', 'nullable', Rule::enum(TicketPriority::class)],
            'due_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function priority(): TicketPriority
    {
        $value = $this->string('priority')->toString();

        return $value === '' ? TicketPriority::Normal : TicketPriority::from($value);
    }
}
