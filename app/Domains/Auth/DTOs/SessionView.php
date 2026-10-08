<?php

namespace App\Domains\Auth\DTOs;

use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Support\Collection;

/**
 * Ce que l'appelant voit de sa propre session : son compte, l'espace dans
 * lequel il agit, ce qu'il peut y faire, et les autres espaces qu'il peut
 * rejoindre.
 */
final readonly class SessionView
{
    /**
     * @param  Collection<int, WorkspaceMember>  $memberships
     * @param  list<string>  $permissions  celles du rôle tenu dans l'espace courant
     */
    public function __construct(
        public User $user,
        public ?WorkspaceMember $currentMembership,
        public Collection $memberships,
        public array $permissions,
        public bool $isPlatformAdmin,
    ) {}
}
