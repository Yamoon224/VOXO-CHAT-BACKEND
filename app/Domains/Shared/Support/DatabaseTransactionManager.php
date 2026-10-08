<?php

namespace App\Domains\Shared\Support;

use App\Domains\Shared\Contracts\TransactionManagerContract;
use Closure;
use Illuminate\Database\ConnectionInterface;

final class DatabaseTransactionManager implements TransactionManagerContract
{
    public function __construct(private readonly ConnectionInterface $connection) {}

    public function run(Closure $work): mixed
    {
        return $this->connection->transaction($work);
    }
}
