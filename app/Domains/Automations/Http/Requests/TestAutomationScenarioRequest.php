<?php

namespace App\Domains\Automations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestAutomationScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
            // Canal d'exemple pour simuler une condition « channel » — en
            // texte libre, pas l'enum `ConversationChannel` : ce domaine ne
            // doit dépendre de `Conversations` que par ses contrats.
            'channel' => ['sometimes', 'nullable', 'string', 'max:40'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['message' => 'message', 'channel' => 'canal'];
    }
}
