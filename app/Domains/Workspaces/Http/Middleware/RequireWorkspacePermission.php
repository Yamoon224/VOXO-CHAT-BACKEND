<?php

namespace App\Domains\Workspaces\Http\Middleware;

use App\Domains\Shared\Exceptions\PermissionDeniedException;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Workspaces\Contracts\PermissionMatrixContract;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige que le rôle de l'appelant dans l'espace courant accorde une
 * permission. S'utilise après `workspace`, qui résout le périmètre.
 */
final class RequireWorkspacePermission
{
    public function __construct(private readonly PermissionMatrixContract $matrix) {}

    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $scope = WorkspaceScope::fromRequest($request);

        if (! $this->matrix->allows($scope->role, $permission)) {
            throw PermissionDeniedException::forPermission($permission);
        }

        return $next($request);
    }
}
