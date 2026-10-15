<?php

namespace Tests\Support\Fakes;

use App\Domains\Automations\Contracts\AutomationRunRepositoryContract;
use App\Domains\Automations\Enums\AutomationRunStatus;
use App\Models\AutomationRun;

final class InMemoryAutomationRunRepository implements AutomationRunRepositoryContract
{
    /** @var array<string, AutomationRun> */
    private array $runs = [];

    public function __construct(AutomationRun ...$runs)
    {
        foreach ($runs as $run) {
            $this->runs[$run->id] = $run;
        }
    }

    public function findActiveForConversation(string $conversationId): ?AutomationRun
    {
        foreach ($this->runs as $run) {
            if ($run->conversation_id === $conversationId && $run->status === AutomationRunStatus::Running) {
                return $run;
            }
        }

        return null;
    }

    public function create(array $attributes): AutomationRun
    {
        $run = ModelFactory::automationRun($attributes);

        return $this->runs[$run->id] = $run;
    }

    public function update(AutomationRun $run, array $attributes): AutomationRun
    {
        $run->setRawAttributes($attributes + $run->getAttributes(), true);

        return $run;
    }
}
