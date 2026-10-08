<?php

namespace App\Domains\Workspaces\Http\Requests;

use App\Domains\Shared\Enums\WorkspaceRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['role' => ['required', Rule::in(WorkspaceRole::assignableValues())]];
    }

    public function role(): WorkspaceRole
    {
        return WorkspaceRole::from($this->string('role')->toString());
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['role' => 'rôle'];
    }
}
