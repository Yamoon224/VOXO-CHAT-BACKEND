<?php

namespace App\Domains\Workspaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'min:2', 'max:120']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => "nom de l'espace de travail"];
    }
}
