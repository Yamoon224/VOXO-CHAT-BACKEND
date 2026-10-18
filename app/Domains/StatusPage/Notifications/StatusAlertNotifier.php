<?php

namespace App\Domains\StatusPage\Notifications;

use App\Domains\StatusPage\Contracts\StatusSubscriberRepositoryContract;
use App\Domains\StatusPage\Jobs\SendStatusAlertJob;
use App\Models\StatusIncident;
use App\Models\StatusIncidentUpdate;

/** Met en file une alerte par abonné : jamais dans la requête qui vient de créer la mise à jour. */
final class StatusAlertNotifier
{
    public function __construct(private readonly StatusSubscriberRepositoryContract $subscribers) {}

    public function notifyIncidentUpdate(StatusIncident $incident, StatusIncidentUpdate $update): void
    {
        foreach ($this->subscribers->forWorkspace($incident->workspace_id) as $subscriber) {
            SendStatusAlertJob::dispatch($subscriber->id, $incident->title, $update->status->label(), $update->body);
        }
    }
}
