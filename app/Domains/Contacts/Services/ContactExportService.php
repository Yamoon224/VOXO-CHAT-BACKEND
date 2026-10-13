<?php

namespace App\Domains\Contacts\Services;

use App\Domains\Contacts\Contracts\ContactRepositoryContract;

/** Export CSV (section 2.6) : les mêmes colonnes que l'import accepte, pour l'aller-retour. */
final class ContactExportService
{
    public function __construct(private readonly ContactRepositoryContract $contacts) {}

    public function export(string $workspaceId): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['name', 'email', 'phone', 'custom_attributes']);

        foreach ($this->contacts->all($workspaceId) as $contact) {
            fputcsv($handle, [
                $contact->name,
                $contact->email,
                $contact->phone,
                $contact->custom_attributes !== null ? json_encode($contact->custom_attributes) : null,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
