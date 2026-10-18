<?php

namespace App\Domains\Integrations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nom'];
    }
}
