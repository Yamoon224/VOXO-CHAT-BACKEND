<?php

namespace App\Domains\Workspaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * `name` et `password` ne sont exigés que si l'invité doit créer un compte,
 * ce que seul le service peut déterminer : ici, on ne valide que leur forme.
 */
class AcceptInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'password' => ['sometimes', 'nullable', 'confirmed', Password::min(8)],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nom', 'password' => 'mot de passe'];
    }
}
