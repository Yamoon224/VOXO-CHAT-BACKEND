<?php

namespace App\Domains\StatusPage\Contracts;

use App\Models\StatusSubscriber;
use Illuminate\Support\Collection;

interface StatusSubscriberRepositoryContract
{
    /** @return Collection<int, StatusSubscriber> */
    public function forWorkspace(string $workspaceId): Collection;

    public function findInWorkspace(string $workspaceId, string $id): ?StatusSubscriber;

    /** Sans portée d'espace : pour `SendStatusAlertJob`, qui ne connaît que l'identifiant mis en file. */
    public function findById(string $id): ?StatusSubscriber;

    public function findByEmail(string $workspaceId, string $email): ?StatusSubscriber;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): StatusSubscriber;

    public function delete(StatusSubscriber $subscriber): void;
}
