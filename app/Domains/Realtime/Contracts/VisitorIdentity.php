<?php

namespace App\Domains\Realtime\Contracts;

/**
 * Identité minimale d'un visiteur anonyme, telle que le garde `visitor` la
 * reconstruit (voir `Widget\Support\VisitorPrincipal`, qui l'implémente) —
 * pour que `ConversationChannelAuthorizer` la reconnaisse sans jamais nommer
 * sa classe concrète (limite de domaine, voir `DomainBoundariesTest`).
 */
interface VisitorIdentity
{
    public function workspaceId(): string;

    public function visitorId(): string;
}
