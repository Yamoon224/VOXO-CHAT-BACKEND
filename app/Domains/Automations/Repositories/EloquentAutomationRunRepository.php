<?php

namespace App\Domains\Automations\Repositories;

use App\Domains\Automations\Contracts\AutomationRunRepositoryContract;
use App\Domains\Automations\Enums\AutomationRunStatus;
use App\Models\AutomationRun;

final class EloquentAutomationRunRepository implements AutomationRunRepositoryContract
{
    public function findActiveForConversation(string $conversationId): ?AutomationRun
    {
        return AutomationRun::query()
            ->where('conversation_id', $conversationId)
            ->where('status', AutomationRunStatus::Running)
            ->first();
    }

    public function create(array $attributes): AutomationRun
    {
        return AutomationRun::query()->create($attributes);
    }

    public function update(AutomationRun $run, array $attributes): AutomationRun
    {
        $run->update($attributes);

        return $run;
    }
}
