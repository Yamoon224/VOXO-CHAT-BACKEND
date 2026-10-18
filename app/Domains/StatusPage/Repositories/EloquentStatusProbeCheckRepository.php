<?php

namespace App\Domains\StatusPage\Repositories;

use App\Domains\StatusPage\Contracts\StatusProbeCheckRepositoryContract;
use App\Domains\StatusPage\Enums\ProbeStatus;
use App\Models\StatusProbeCheck;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class EloquentStatusProbeCheckRepository implements StatusProbeCheckRepositoryContract
{
    public function forProbe(string $probeId, int $limit): Collection
    {
        return StatusProbeCheck::query()->where('probe_id', $probeId)->latest('checked_at')->limit($limit)->get();
    }

    public function uptimeRatio(string $probeId, Carbon $since): ?float
    {
        $checks = StatusProbeCheck::query()->where('probe_id', $probeId)->where('checked_at', '>=', $since)->get(['status']);

        if ($checks->isEmpty()) {
            return null;
        }

        return $checks->filter(fn (StatusProbeCheck $check) => $check->status === ProbeStatus::Up)->count() / $checks->count();
    }

    public function create(array $attributes): StatusProbeCheck
    {
        return StatusProbeCheck::query()->create($attributes);
    }
}
