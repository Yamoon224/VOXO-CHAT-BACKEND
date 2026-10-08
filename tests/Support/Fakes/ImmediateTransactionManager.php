<?php

namespace Tests\Support\Fakes;

use App\Domains\Shared\Contracts\TransactionManagerContract;
use Closure;

/** Exécute le traitement tel quel : il n'y a pas de base à protéger. */
final class ImmediateTransactionManager implements TransactionManagerContract
{
    public function run(Closure $work): mixed
    {
        return $work();
    }
}
