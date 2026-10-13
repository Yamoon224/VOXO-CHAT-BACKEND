<?php

namespace App\Domains\Contacts\Services;

use App\Domains\Contacts\Contracts\ContactRepositoryContract;
use App\Domains\Contacts\Contracts\ContactSegmentRepositoryContract;
use App\Domains\Contacts\Enums\SegmentOperator;
use App\Models\Contact;
use App\Models\ContactSegment;
use Illuminate\Support\Collection;

/**
 * Segments (section 2.6) : liste de critères `{field, operator, value}`
 * évaluée en mémoire sur les contacts de l'espace. Volontairement un
 * évaluateur simple, pas un moteur de requête dynamique — la volumétrie
 * visée (un espace de travail, pas une base multi-tenant entière) le permet.
 */
final class ContactSegmentService
{
    public function __construct(
        private readonly ContactSegmentRepositoryContract $segments,
        private readonly ContactRepositoryContract $contacts,
    ) {}

    /** @return Collection<int, ContactSegment> */
    public function list(string $workspaceId): Collection
    {
        return $this->segments->forWorkspace($workspaceId);
    }

    /** @param  array<string, mixed>  $attributes */
    public function create(string $workspaceId, array $attributes): ContactSegment
    {
        return $this->segments->create([...$attributes, 'workspace_id' => $workspaceId]);
    }

    /** @param  array<string, mixed>  $attributes */
    public function update(string $workspaceId, string $segmentId, array $attributes): ContactSegment
    {
        return $this->segments->update($this->segments->findInWorkspaceOrFail($workspaceId, $segmentId), $attributes);
    }

    public function delete(string $workspaceId, string $segmentId): void
    {
        $this->segments->delete($this->segments->findInWorkspaceOrFail($workspaceId, $segmentId));
    }

    /** @return Collection<int, Contact> Les contacts qui remplissent tous les critères du segment. */
    public function matching(string $workspaceId, string $segmentId): Collection
    {
        $segment = $this->segments->findInWorkspaceOrFail($workspaceId, $segmentId);

        return $this->contacts->all($workspaceId)->filter(
            fn (Contact $contact) => $this->matchesAllCriteria($contact, $segment->criteria),
        )->values();
    }

    /** @param  list<array{field: string, operator: string, value: mixed}>  $criteria */
    private function matchesAllCriteria(Contact $contact, array $criteria): bool
    {
        foreach ($criteria as $criterion) {
            if (! $this->matchesCriterion($contact, $criterion)) {
                return false;
            }
        }

        return true;
    }

    /** @param  array{field: string, operator: string, value: mixed}  $criterion */
    private function matchesCriterion(Contact $contact, array $criterion): bool
    {
        $value = $this->readField($contact, $criterion['field']);
        $operator = SegmentOperator::from($criterion['operator']);
        $target = $criterion['value'] ?? null;

        return match ($operator) {
            SegmentOperator::IsSet => $value !== null && $value !== '',
            SegmentOperator::IsNotSet => $value === null || $value === '',
            SegmentOperator::Equals => is_string($value) && is_string($target) && strcasecmp($value, $target) === 0,
            SegmentOperator::Contains => is_string($value) && is_string($target) && str_contains(
                mb_strtolower($value),
                mb_strtolower($target),
            ),
        };
    }

    private function readField(Contact $contact, string $field): mixed
    {
        if (in_array($field, ['name', 'email', 'phone'], true)) {
            return $contact->{$field};
        }

        return $contact->custom_attributes[$field] ?? null;
    }
}
