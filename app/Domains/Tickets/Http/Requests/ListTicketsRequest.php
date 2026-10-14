<?php

namespace App\Domains\Tickets\Http\Requests;

use App\Domains\Tickets\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTicketsRequest extends FormRequest
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
            'overdue' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
