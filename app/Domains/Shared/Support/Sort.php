<?php

namespace App\Domains\Shared\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Tri d'une liste demandé par le client.
 *
 * Le nom de colonne ne vient jamais de la requête : le client envoie une clé
 * publique que chaque dépôt traduit, via une liste fermée, en colonne ou en
 * expression SQL.
 */
final class Sort
{
    /**
     * Marque une expression SQL, par opposition à un simple nom de colonne.
     * L'expression provient toujours d'une constante de dépôt, jamais de la
     * requête : c'est ce qui la rend sûre à interpoler.
     *
     * @return array{0: string, 1: string}
     */
    public static function raw(string $expression): array
    {
        return ['raw', $expression];
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters  contient éventuellement `sort` et `direction`
     * @param  array<string, string|array{0: string, 1: string}>  $allowed  clé publique vers colonne, ou Sort::raw(...)
     * @return Builder<TModel>
     */
    public static function apply(
        Builder $query,
        array $filters,
        array $allowed,
        string $fallbackColumn = 'id',
        string $fallbackDirection = 'desc',
    ): Builder {
        $direction = self::direction($filters);
        $key = $filters['sort'] ?? null;

        // Une clé inconnue est ignorée plutôt que refusée : un tri est un
        // confort d'affichage, pas une instruction dont l'échec doit coûter
        // une page d'erreur.
        if (! is_string($key) || ! array_key_exists($key, $allowed)) {
            return $query->orderBy($fallbackColumn, $fallbackDirection);
        }

        $target = $allowed[$key];

        if (is_array($target)) {
            return $query->orderByRaw($target[1].' '.$direction)->orderBy('id', 'desc');
        }

        // Un tri par colonne non unique laisserait des lignes se croiser d'une
        // page à l'autre : on départage toujours par identifiant.
        return $query->orderBy($target, $direction)->orderBy('id', 'desc');
    }

    /**
     * Toute autre valeur que `desc` retombe sur `asc` : la direction est ainsi
     * bornée à deux mots-clés avant d'approcher le SQL.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function direction(array $filters): string
    {
        $direction = $filters['direction'] ?? '';

        return is_string($direction) && strtolower($direction) === 'desc' ? 'desc' : 'asc';
    }
}
