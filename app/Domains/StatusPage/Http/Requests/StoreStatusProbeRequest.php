<?php

namespace App\Domains\StatusPage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStatusProbeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url', 'max:2048'],
            'check_interval_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nom', 'url' => 'URL', 'check_interval_minutes' => 'intervalle (minutes)'];
    }

    /** @return array{name: string, url: string, check_interval_minutes: int} */
    public function toAttributes(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'url' => $this->string('url')->toString(),
            'check_interval_minutes' => $this->integer('check_interval_minutes', 5),
        ];
    }
}
