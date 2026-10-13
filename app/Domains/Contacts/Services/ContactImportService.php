<?php

namespace App\Domains\Contacts\Services;

use App\Domains\Contacts\Contracts\ContactEventRepositoryContract;
use App\Domains\Contacts\Contracts\ContactRepositoryContract;
use App\Domains\Contacts\Exceptions\ContactImportInvalidException;
use Illuminate\Http\UploadedFile;

/** Import CSV (section 2.6) : une colonne `email` requise, `name`/`phone` reconnues, le reste devient des attributs personnalisés. */
final class ContactImportService
{
    public function __construct(
        private readonly ContactRepositoryContract $contacts,
        private readonly ContactEventRepositoryContract $events,
    ) {}

    /** @return array{created: int, updated: int} */
    public function import(string $workspaceId, UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw ContactImportInvalidException::empty();
        }

        try {
            $header = fgetcsv($handle);

            if ($header === false) {
                throw ContactImportInvalidException::empty();
            }

            $columns = array_map(static fn (string $column) => mb_strtolower(trim($column)), $header);
            $emailIndex = array_search('email', $columns, true);

            if ($emailIndex === false) {
                throw ContactImportInvalidException::missingEmailColumn();
            }

            $created = 0;
            $updated = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $email = trim((string) ($row[$emailIndex] ?? ''));

                if ($email === '') {
                    continue;
                }

                $attributes = $this->rowToAttributes($columns, $row, $emailIndex);
                $existing = $this->contacts->findByEmail($workspaceId, $email);

                if ($existing !== null) {
                    $this->contacts->update($existing, $attributes);
                    $contactId = $existing->id;
                    $updated++;
                } else {
                    $contactId = $this->contacts->create([...$attributes, 'workspace_id' => $workspaceId, 'email' => $email])->id;
                    $created++;
                }

                $this->events->record($workspaceId, $contactId, 'imported');
            }

            return ['created' => $created, 'updated' => $updated];
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string|null>  $row
     * @return array<string, mixed>
     */
    private function rowToAttributes(array $columns, array $row, int $emailIndex): array
    {
        $attributes = [];
        $customAttributes = [];

        foreach ($columns as $index => $column) {
            if ($index === $emailIndex) {
                continue;
            }

            $value = trim((string) ($row[$index] ?? ''));

            if (in_array($column, ['name', 'phone'], true)) {
                $attributes[$column] = $value !== '' ? $value : null;
            } elseif ($column !== '') {
                $customAttributes[$column] = $value;
            }
        }

        if ($customAttributes !== []) {
            $attributes['custom_attributes'] = $customAttributes;
        }

        return $attributes;
    }
}
