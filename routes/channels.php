<?php

use App\Domains\Widget\Support\VisitorPrincipal;
use App\Domains\Workspaces\Contracts\MembershipReaderContract;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/**
 * Deux canaux par conversation (lot 2, temps réel — section 4.1) :
 *
 * - `conversation.{id}` — réservé aux agents, authentifiés par Sanctum
 *   (garde `sanctum`) : tous les messages, notes internes comprises.
 * - `conversation.{id}.public` — ouvert au visiteur propriétaire de la
 *   conversation (garde `visitor`, voir `VisitorPrincipal`) ET aux agents :
 *   messages publics seulement, présence, indicateurs de saisie (relayés
 *   côté client, sans code serveur — voir la diffusion du whisper Pusher).
 *
 * Jamais l'inverse : une note interne ne doit jamais rejoindre le second
 * canal, sans quoi elle atteindrait le navigateur du visiteur (voir
 * `MessageBroadcastEvent::broadcastOn()`).
 */
Broadcast::channel('conversation.{conversationId}', function ($user, string $conversationId) {
    if (! $user instanceof User) {
        return false;
    }

    $conversation = Conversation::query()->find($conversationId);

    if ($conversation === null) {
        return false;
    }

    $member = app(MembershipReaderContract::class)->findForUser($conversation->workspace_id, $user->id);

    return $member !== null ? ['id' => $user->id, 'name' => $user->name] : false;
}, ['guards' => ['sanctum']]);

Broadcast::channel('conversation.{conversationId}.public', function ($user, string $conversationId) {
    $conversation = Conversation::query()->find($conversationId);

    if ($conversation === null) {
        return false;
    }

    if ($user instanceof User) {
        $member = app(MembershipReaderContract::class)->findForUser($conversation->workspace_id, $user->id);

        return $member !== null ? ['id' => $user->id, 'name' => $user->name] : false;
    }

    if ($user instanceof VisitorPrincipal) {
        return $user->workspaceId === $conversation->workspace_id && $user->visitorId === $conversation->visitor_id
            ? ['id' => "visitor:{$user->visitorId}", 'name' => 'Visiteur']
            : false;
    }

    return false;
}, ['guards' => ['sanctum', 'visitor']]);
