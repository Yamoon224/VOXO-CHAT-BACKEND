<?php

namespace App\Domains\Assistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SandboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['message' => ['required', 'string', 'min:1', 'max:2000']];
    }
}
