<?php

namespace App\Domains\StatusPage\Http\Requests;

use App\Domains\StatusPage\Enums\IncidentImpact;
use App\Domains\StatusPage\Enums\IncidentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStatusIncidentRequest extends FormRequest
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
            'impact' => ['required', Rule::in(IncidentImpact::values())],
            'status' => ['required', Rule::in(IncidentStatus::values())],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['title' => 'titre', 'impact' => 'impact', 'status' => 'statut', 'body' => 'message'];
    }

    /** @return array{title: string, impact: string, status: string, body: string} */
    public function toAttributes(): array
    {
        return [
            'title' => $this->string('title')->toString(),
            'impact' => $this->string('impact')->toString(),
            'status' => $this->string('status')->toString(),
            'body' => $this->string('body')->toString(),
        ];
    }
}
