<?php

namespace App\Domains\Conversations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCannedResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:120'],
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }
}
