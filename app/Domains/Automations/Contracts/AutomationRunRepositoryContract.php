<?php

namespace App\Domains\Automations\Contracts;

use App\Models\AutomationRun;

interface AutomationRunRepositoryContract
{
    /** `null` si aucune exécution n'est en cours pour cette conversation. */
    public function findActiveForConversation(string $conversationId): ?AutomationRun;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): AutomationRun;

    /** @param  array<string, mixed>  $attributes */
    public function update(AutomationRun $run, array $attributes): AutomationRun;
}
