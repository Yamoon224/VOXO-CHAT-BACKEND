<?php

namespace App\Domains\Shared\Http\Middleware;

use App\Domains\Shared\Exceptions\PermissionDeniedException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige le rôle `platform_admin`, porté par le compte lui-même (jamais par
 * une adhésion à un espace de travail) — voir `App\Models\User`.
 */
final class RequirePlatformAdmin
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->hasRole(User::PLATFORM_ADMIN_ROLE)) {
            throw PermissionDeniedException::forPermission('platform.manage');
        }

        return $next($request);
    }
}
