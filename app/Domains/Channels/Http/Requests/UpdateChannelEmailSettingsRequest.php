<?php

namespace App\Domains\Channels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChannelEmailSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'enabled' => ['sometimes', 'required', 'boolean'],
            'reply_from_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'reply_from_name' => ['sometimes', 'nullable', 'string', 'max:120'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['enabled' => 'activé', 'reply_from_email' => "adresse d'envoi", 'reply_from_name' => "nom d'expéditeur"];
    }
}
