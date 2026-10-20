<?php

namespace App\Domains\Widget\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListVisitorMessagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `token` en requête reste accepté pour compatibilité, mais
     * `X-Visitor-Token` (même en-tête que l'authentification des canaux
     * temps réel, voir `DomainServiceProvider::boot()`) est préféré : un
     * jeton de session dans l'URL finit dans les journaux serveur et
     * l'historique du navigateur.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => [$this->header('X-Visitor-Token') ? 'nullable' : 'required', 'string'],
        ];
    }

    public function visitorToken(): string
    {
        return (string) ($this->header('X-Visitor-Token') ?? $this->string('token')->toString());
    }
}
