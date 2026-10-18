<?php

namespace App\Domains\StatusPage\Http\Requests;

use App\Domains\StatusPage\Enums\IncidentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddStatusIncidentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(IncidentStatus::values())],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['status' => 'statut', 'body' => 'message'];
    }

    /** @return array{status: string, body: string} */
    public function toAttributes(): array
    {
        return ['status' => $this->string('status')->toString(), 'body' => $this->string('body')->toString()];
    }
}
