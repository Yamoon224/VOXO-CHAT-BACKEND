<?php

namespace App\Domains\Widget\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListVisitorMessagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['token' => ['required', 'string']];
    }
}
