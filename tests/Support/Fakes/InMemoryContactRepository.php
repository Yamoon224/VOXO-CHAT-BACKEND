<?php

namespace Tests\Support\Fakes;

use App\Domains\Contacts\Contracts\ContactRepositoryContract;
use App\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

final class InMemoryContactRepository implements ContactRepositoryContract
{
    /** @var array<string, Contact> */
    private array $contacts = [];

    public function __construct(Contact ...$contacts)
    {
        foreach ($contacts as $contact) {
            $this->contacts[$contact->id] = $contact;
        }
    }

    public function paginate(string $workspaceId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $items = $this->all($workspaceId)->values()->all();

        return new Paginator($items, count($items), $perPage);
    }

    public function findInWorkspaceOrFail(string $workspaceId, string $contactId): Contact
    {
        $contact = $this->contacts[$contactId] ?? null;

        if ($contact === null || $contact->workspace_id !== $workspaceId) {
            throw new ModelNotFoundException;
        }

        return $contact;
    }

    public function findByEmail(string $workspaceId, string $email): ?Contact
    {
        foreach ($this->contacts as $contact) {
            if ($contact->workspace_id === $workspaceId && $contact->email === $email) {
                return $contact;
            }
        }

        return null;
    }

    public function findByPhone(string $workspaceId, string $phone): ?Contact
    {
        foreach ($this->contacts as $contact) {
            if ($contact->workspace_id === $workspaceId && $contact->phone === $phone) {
                return $contact;
            }
        }

        return null;
    }

    public function findByCustomAttribute(string $workspaceId, string $key, string $value): ?Contact
    {
        foreach ($this->contacts as $contact) {
            if ($contact->workspace_id === $workspaceId && ($contact->custom_attributes[$key] ?? null) === $value) {
                return $contact;
            }
        }

        return null;
    }

    public function create(array $attributes): Contact
    {
        $contact = ModelFactory::contact($this->encodeJsonColumns($attributes));

        return $this->contacts[$contact->id] = $contact;
    }

    public function update(Contact $contact, array $attributes): Contact
    {
        $contact->setRawAttributes($this->encodeJsonColumns($attributes) + $contact->getAttributes(), true);

        return $contact;
    }

    /**
     * `custom_attributes` est un cast JSON : comme une vraie ligne, l'attribut
     * brut doit être une chaîne, jamais le tableau PHP que le service fournit.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function encodeJsonColumns(array $attributes): array
    {
        if (array_key_exists('custom_attributes', $attributes) && is_array($attributes['custom_attributes'])) {
            $attributes['custom_attributes'] = json_encode($attributes['custom_attributes']);
        }

        return $attributes;
    }

    public function delete(Contact $contact): void
    {
        unset($this->contacts[$contact->id]);
    }

    /** @return Collection<int, Contact> */
    public function all(string $workspaceId): Collection
    {
        return (new Collection($this->contacts))
            ->filter(fn (Contact $contact) => $contact->workspace_id === $workspaceId)
            ->values();
    }
}
