<?php

namespace App\Domains\Knowledge\Http\Requests;

use App\Domains\Knowledge\Enums\RecrawlFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKnowledgeWebsiteSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'min:2', 'max:120'],
            'url' => ['required', 'url', 'max:2048'],
            'sitemap_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'recrawl_frequency' => ['required', Rule::enum(RecrawlFrequency::class)],
        ];
    }

    public function frequency(): RecrawlFrequency
    {
        return RecrawlFrequency::from($this->string('recrawl_frequency')->toString());
    }
}
