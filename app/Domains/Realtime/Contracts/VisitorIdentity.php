<?php

namespace App\Domains\Realtime\Contracts;

/**
 * Identité minimale d'un visiteur anonyme, telle que le garde `visitor` la
 * reconstruit (voir `Widget\Support\VisitorPrincipal`, qui l'implémente) —
 * interface marqueur pour que `ConversationChannelAuthorizer` la reconnaisse
 * sans jamais nommer sa classe concrète (limite de domaine, voir
 * `DomainBoundariesTest`).
 *
 * @property-read string $workspaceId
 * @property-read string $visitorId
 */
interface VisitorIdentity {}
