<?php

namespace App\Domains\HelpCenter\Support;

use Illuminate\Support\Str;

/**
 * Identifiant lisible d'une catégorie ou d'un article, dérivé de son titre —
 * même principe que `WorkspaceSlug` (domaine `Workspaces`), jamais saisi par
 * l'agent : l'URL publique en dépend (section 2.10, référencement).
 */
final class HelpCenterSlug
{
    private const FALLBACK = 'article';

    /** Un titre sans caractère exploitable (« !!! », emoji) retombe sur un slug neutre. */
    public static function base(string $title): string
    {
        $slug = Str::slug($title);

        return $slug === '' ? self::FALLBACK : $slug;
    }

    /** Variante départagée quand le slug de base est déjà pris, à cette tentative. */
    public static function withSuffix(string $base, int $attempt): string
    {
        return $base.'-'.($attempt + 1);
    }
}
