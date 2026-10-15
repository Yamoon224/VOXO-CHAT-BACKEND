<?php

namespace App\Domains\Channels\Http\Requests;

use App\Domains\Channels\Support\MessagingChannel;
use Illuminate\Foundation\Http\FormRequest;

class UpdateChannelConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $channel = MessagingChannel::from((string) $this->route('channel'));

        $rules = [
            'enabled' => ['sometimes', 'required', 'boolean'],
            'credentials' => ['sometimes', 'array'],
        ];

        foreach ($channel->requiredCredentialFields() as $field) {
            $rules["credentials.{$field}"] = ['sometimes', 'nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['enabled' => 'activé', 'credentials' => 'identifiants'];
    }
}
