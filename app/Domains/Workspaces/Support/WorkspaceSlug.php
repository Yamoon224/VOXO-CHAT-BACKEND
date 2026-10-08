<?php

namespace App\Domains\Workspaces\Support;

use Illuminate\Support\Str;

/**
 * Identifiant lisible d'un espace de travail, dérivé de son nom.
 */
final class WorkspaceSlug
{
    public const MAX_BASE_LENGTH = 60;

    private const FALLBACK = 'espace';

    private const SUFFIX_LENGTH = 6;

    /** Un nom sans caractère exploitable (« !!! », emoji) retombe sur un slug neutre. */
    public static function base(string $name): string
    {
        $slug = trim(Str::limit(Str::slug($name), self::MAX_BASE_LENGTH, ''), '-');

        return $slug === '' ? self::FALLBACK : $slug;
    }

    /** Variante départagée quand le slug de base est déjà pris. */
    public static function withSuffix(string $base): string
    {
        return $base.'-'.Str::lower(Str::random(self::SUFFIX_LENGTH));
    }
}
