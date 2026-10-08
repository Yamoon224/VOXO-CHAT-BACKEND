<?php

namespace Tests\Support\Fakes;

use Illuminate\Contracts\Hashing\Hasher;

final class PlainHasher implements Hasher
{
    /** @return array<string, mixed> */
    public function info($hashedValue): array
    {
        return [];
    }

    /** @param  array<string, mixed>  $options */
    public function make($value, array $options = []): string
    {
        return (string) $value;
    }

    /** @param  array<string, mixed>  $options */
    public function check($value, $hashedValue, array $options = []): bool
    {
        return $value === $hashedValue;
    }

    /** @param  array<string, mixed>  $options */
    public function needsRehash($hashedValue, array $options = []): bool
    {
        return false;
    }
}
