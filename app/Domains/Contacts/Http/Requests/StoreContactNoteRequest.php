<?php

namespace App\Domains\Contacts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'min:1', 'max:5000']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['body' => 'texte'];
    }
}
