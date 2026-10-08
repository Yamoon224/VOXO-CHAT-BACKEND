<?php

namespace App\Domains\Workspaces\Http\Middleware;

use App\Domains\Auth\Contracts\AccessTokenManagerContract;
use App\Domains\Shared\Exceptions\WorkspaceRequiredException;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Workspaces\Contracts\MembershipReaderContract;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Résout le périmètre d'espace de travail de la requête à partir du jeton.
 *
 * L'adhésion est relue à chaque requête : un membre retiré de l'équipe perd
 * l'accès immédiatement, sans attendre l'expiration de son jeton.
 */
final class ResolveWorkspaceScope
{
    public function __construct(
        private readonly AccessTokenManagerContract $tokens,
        private readonly MembershipReaderContract $memberships,
    ) {}

    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $workspaceId = $user instanceof User ? $this->tokens->currentWorkspaceId($user) : null;
        $member = $user instanceof User && $workspaceId !== null
            ? $this->memberships->findForUser($workspaceId, $user->id)
            : null;

        if ($member === null) {
            throw WorkspaceRequiredException::make();
        }

        (new WorkspaceScope($member->workspace_id, $member->id, $member->user_id, $member->role))->attachTo($request);

        return $next($request);
    }
}
