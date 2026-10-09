<?php

namespace App\Domains\Payments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['plan_slug' => ['required', 'string']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['plan_slug' => 'palier'];
    }
}
