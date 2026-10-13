<?php

namespace App\Domains\Contacts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportContactsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['file' => 'fichier'];
    }
}
