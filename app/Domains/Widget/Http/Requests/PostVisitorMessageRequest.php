<?php

namespace App\Domains\Widget\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostVisitorMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'body' => ['required', 'string', 'min:1', 'max:5000'],
            'visitor_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'visitor_email' => ['sometimes', 'nullable', 'email', 'max:255'],
        ];
    }
}
