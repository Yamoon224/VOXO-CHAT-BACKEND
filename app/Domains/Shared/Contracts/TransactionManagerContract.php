<?php

namespace App\Domains\Shared\Contracts;

use Closure;

/**
 * Exécute un traitement de façon atomique.
 *
 * Les services orchestrent des écritures qui doivent réussir ou échouer
 * ensemble, sans pour autant connaître le moteur de persistance : ils
 * dépendent de ce contrat plutôt que de la façade de base de données.
 */
interface TransactionManagerContract
{
    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $work
     * @return TResult
     */
    public function run(Closure $work): mixed;
}
