<?php

namespace App\Domains\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public const LOCALES = ['fr', 'en'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
            'workspace_name' => ['required', 'string', 'min:2', 'max:120'],
            'locale' => ['sometimes', Rule::in(self::LOCALES)],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ];
    }

    /** @return array{name: string, email: string, password: string, workspace_name: string, locale?: string} */
    public function registrationData(): array
    {
        /** @var array{name: string, email: string, password: string, workspace_name: string, locale?: string} $data */
        $data = $this->safe()->only(['name', 'email', 'password', 'workspace_name', 'locale']);

        return $data;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'email' => 'adresse e-mail',
            'password' => 'mot de passe',
            'workspace_name' => "nom de l'espace de travail",
        ];
    }
}
