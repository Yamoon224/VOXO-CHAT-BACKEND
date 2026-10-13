<?php

namespace App\Domains\Contacts\Services;

use App\Domains\Contacts\Contracts\ContactLinkContract;
use App\Domains\Contacts\Contracts\ContactRepositoryContract;
use App\Domains\Contacts\Exceptions\ContactEmailTakenException;
use App\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * CRUD du profil client, et implémente `ContactLinkContract` : seul point
 * d'entrée pour qu'un autre domaine (`Widget`, `Channels`) rattache une
 * conversation à un contact sans connaître `Contacts` au-delà de ce contrat.
 */
final class ContactService implements ContactLinkContract
{
    public function __construct(private readonly ContactRepositoryContract $contacts) {}

    /** @param  array<string, mixed>  $filters */
    public function paginate(string $workspaceId, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->contacts->paginate($workspaceId, $filters, $perPage);
    }

    public function find(string $workspaceId, string $contactId): Contact
    {
        return $this->contacts->findInWorkspaceOrFail($workspaceId, $contactId);
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws ContactEmailTakenException
     */
    public function create(string $workspaceId, array $attributes): Contact
    {
        $this->guardEmailAvailable($workspaceId, $attributes['email'] ?? null);

        return $this->contacts->create([...$attributes, 'workspace_id' => $workspaceId]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws ContactEmailTakenException
     */
    public function update(string $workspaceId, string $contactId, array $attributes): Contact
    {
        $contact = $this->contacts->findInWorkspaceOrFail($workspaceId, $contactId);

        if (array_key_exists('email', $attributes) && $attributes['email'] !== $contact->email) {
            $this->guardEmailAvailable($workspaceId, $attributes['email']);
        }

        return $this->contacts->update($contact, $attributes);
    }

    public function delete(string $workspaceId, string $contactId): void
    {
        $this->contacts->delete($this->contacts->findInWorkspaceOrFail($workspaceId, $contactId));
    }

    public function findOrCreateIdByEmail(string $workspaceId, ?string $email, ?string $name = null): ?string
    {
        if ($email === null || $email === '') {
            return null;
        }

        $contact = $this->contacts->findByEmail($workspaceId, $email);

        if ($contact !== null) {
            return $contact->id;
        }

        return $this->contacts->create([
            'workspace_id' => $workspaceId,
            'email' => $email,
            'name' => $name,
        ])->id;
    }

    private function guardEmailAvailable(string $workspaceId, ?string $email): void
    {
        if ($email === null) {
            return;
        }

        if ($this->contacts->findByEmail($workspaceId, $email) !== null) {
            throw ContactEmailTakenException::make();
        }
    }
}
