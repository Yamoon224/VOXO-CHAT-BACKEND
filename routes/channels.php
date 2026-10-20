<?php

use App\Domains\Realtime\Support\ConversationChannelAuthorizer;
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
 * L'autorisation elle-même vit dans `ConversationChannelAuthorizer`, pour
 * être testable sans passer par le protocole Pusher/Reverb complet. Jamais
 * l'inverse des deux canaux : une note interne ne doit jamais rejoindre le
 * second, sans quoi elle atteindrait le navigateur du visiteur (voir
 * `MessageBroadcastEvent::broadcastOn()`).
 */
Broadcast::channel(
    'conversation.{conversationId}',
    fn (mixed $user, string $conversationId) => app(ConversationChannelAuthorizer::class)->authorizeAgentChannel($user, $conversationId),
    ['guards' => ['sanctum']],
);

Broadcast::channel(
    'conversation.{conversationId}.public',
    fn (mixed $user, string $conversationId) => app(ConversationChannelAuthorizer::class)->authorizePublicChannel($user, $conversationId),
    ['guards' => ['sanctum', 'visitor']],
);
