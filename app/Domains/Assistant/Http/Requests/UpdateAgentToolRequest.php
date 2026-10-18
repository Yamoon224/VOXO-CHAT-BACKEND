<?php

namespace App\Domains\Assistant\Http\Requests;

use App\Domains\Assistant\Enums\AgentToolHttpMethod;
use App\Domains\Assistant\Enums\AgentToolParameterLocation;
use App\Domains\Assistant\Enums\AgentToolParameterType;
use App\Domains\Assistant\Support\TemplatedUrlRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgentToolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'description' => ['required', 'string', 'max:1000'],
            'http_method' => ['required', Rule::in(AgentToolHttpMethod::values())],
            'url' => ['required', 'max:2048', new TemplatedUrlRule],
            'headers' => ['sometimes', 'nullable', 'array'],
            'headers.*' => ['string'],
            'enabled' => ['sometimes', 'boolean'],
            'parameters' => ['sometimes', 'array'],
            'parameters.*.name' => ['required_with:parameters', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'parameters.*.type' => ['required_with:parameters', Rule::in(AgentToolParameterType::values())],
            'parameters.*.description' => ['required_with:parameters', 'string', 'max:500'],
            'parameters.*.required' => ['sometimes', 'boolean'],
            'parameters.*.in' => ['required_with:parameters', Rule::in(AgentToolParameterLocation::values())],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nom', 'description' => 'description', 'http_method' => 'méthode HTTP', 'url' => 'URL'];
    }

    /** @return array{name: string, description: string, http_method: string, url: string, headers: ?array<string, string>, enabled: bool, parameters: list<array{name: string, type: string, description: string, required: bool, in: string}>} */
    public function toAttributes(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'description' => $this->string('description')->toString(),
            'http_method' => $this->string('http_method')->toString(),
            'url' => $this->string('url')->toString(),
            'headers' => $this->input('headers'),
            'enabled' => $this->boolean('enabled', true),
            'parameters' => array_map(fn (array $parameter) => [
                'name' => $parameter['name'],
                'type' => $parameter['type'],
                'description' => $parameter['description'],
                'required' => (bool) ($parameter['required'] ?? false),
                'in' => $parameter['in'],
            ], $this->input('parameters', [])),
        ];
    }
}
