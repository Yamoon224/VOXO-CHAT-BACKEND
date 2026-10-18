<?php

namespace App\Domains\Contacts\Contracts;

use App\Models\Contact;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * Lecture étroite exposée aux autres domaines (`Campaigns`, lot 6, section
 * 2.9 : ciblage par segment) : ils résolvent l'appartenance à un segment,
 * ils ne gèrent pas les segments eux-mêmes.
 */
interface ContactSegmentMatcherContract
{
    /** @return Collection<int, Contact> Les contacts qui remplissent tous les critères du segment. */
    public function matching(string $workspaceId, string $segmentId): Collection;

    /** @throws ModelNotFoundException Le segment n'existe pas, ou pas dans cet espace. */
    public function assertExistsInWorkspace(string $workspaceId, string $segmentId): void;
}
