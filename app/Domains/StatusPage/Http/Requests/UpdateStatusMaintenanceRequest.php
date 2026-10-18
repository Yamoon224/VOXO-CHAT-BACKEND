<?php

namespace App\Domains\StatusPage\Http\Requests;

use App\Domains\StatusPage\Enums\MaintenanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(MaintenanceStatus::values())],
            'scheduled_start' => ['required', 'date'],
            'scheduled_end' => ['required', 'date', 'after:scheduled_start'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'title' => 'titre',
            'description' => 'description',
            'scheduled_start' => 'début prévu',
            'scheduled_end' => 'fin prévue',
        ];
    }

    /** @return array{title: string, description: ?string, status: string, scheduled_start: string, scheduled_end: string} */
    public function toAttributes(): array
    {
        return [
            'title' => $this->string('title')->toString(),
            'description' => $this->filled('description') ? $this->string('description')->toString() : null,
            'status' => $this->string('status')->toString(),
            'scheduled_start' => $this->string('scheduled_start')->toString(),
            'scheduled_end' => $this->string('scheduled_end')->toString(),
        ];
    }
}
