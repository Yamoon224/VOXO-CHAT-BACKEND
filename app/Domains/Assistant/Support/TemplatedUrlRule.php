<?php

namespace App\Domains\Assistant\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Une URL d'outil (section 2.2, P3) peut porter des `{param}` substitués à
 * l'appel — la règle `url` native rejette les accolades, donc elles sont
 * remplacées par une valeur neutre avant de revalider la forme de l'URL.
 */
final class TemplatedUrlRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || filter_var(preg_replace('/\{[^{}]+\}/', 'x', $value), FILTER_VALIDATE_URL) === false) {
            $fail('validation.url')->translate();
        }
    }
}
