<?php

namespace App\Domains\Workspaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceRequest extends FormRequest
{
    public const LOCALES = ['fr', 'en'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:120'],
            'locale' => ['sometimes', 'required', Rule::in(self::LOCALES)],
            'timezone' => ['sometimes', 'required', 'timezone:all'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => "nom de l'espace de travail", 'locale' => 'langue', 'timezone' => 'fuseau horaire'];
    }
}
