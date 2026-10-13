<?php

namespace App\Domains\Contacts\Contracts;

use App\Models\ContactSegment;
use Illuminate\Support\Collection;

interface ContactSegmentRepositoryContract
{
    /** @return Collection<int, ContactSegment> */
    public function forWorkspace(string $workspaceId): Collection;

    public function findInWorkspaceOrFail(string $workspaceId, string $segmentId): ContactSegment;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): ContactSegment;

    /** @param  array<string, mixed>  $attributes */
    public function update(ContactSegment $segment, array $attributes): ContactSegment;

    public function delete(ContactSegment $segment): void;
}
