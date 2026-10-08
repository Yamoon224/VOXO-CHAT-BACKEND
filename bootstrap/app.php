<?php

use App\Domains\Shared\Exceptions\DomainException;
use App\Domains\Workspaces\Http\Middleware\RequireWorkspacePermission;
use App\Domains\Workspaces\Http\Middleware\ResolveWorkspaceScope;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            // Résout le périmètre d'espace de travail à partir du jeton.
            'workspace' => ResolveWorkspaceScope::class,
            // Exige une permission du rôle tenu dans cet espace.
            'workspace.can' => RequireWorkspacePermission::class,
        ]);

        // API pure, sans page de connexion côté serveur : ne jamais tenter de
        // rediriger un invité vers une route `login` qui n'existe pas.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Gestion centralisée des erreurs : un seul endroit décide de la forme
         * d'une réponse d'erreur, et les contrôleurs n'interceptent rien.
         *
         * Contrat : { "message": string, "error_code": string, "context": object }
         * enrichi de "errors" pour les erreurs de validation (422).
         */
        $wantsJson = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        /** @param  array<string, mixed>  $context */
        $error = fn (string $message, string $code, int $status, array $context = []): JsonResponse => response()->json([
            'message' => $message,
            'error_code' => $code,
            'context' => (object) $context,
        ], $status);

        $exceptions->shouldRenderJsonWhen($wantsJson);

        // La validation garde la forme native de Laravel (`errors` par champ),
        // enrichie du code applicatif.
        $exceptions->render(fn (ValidationException $exception, Request $request) => $wantsJson($request)
            ? response()->json([
                'message' => $exception->getMessage(),
                'error_code' => 'validation_failed',
                'errors' => $exception->errors(),
                'context' => (object) [],
            ], 422)
            : null);

        $exceptions->render(fn (DomainException $exception, Request $request) => $wantsJson($request)
            ? $exception->render()
            : null);

        $exceptions->render(fn (AuthenticationException $exception, Request $request) => $wantsJson($request)
            ? $error('Non authentifié. Reconnectez-vous.', 'unauthenticated', 401)
            : null);

        // Un modèle introuvable est un 404 métier, pas une fuite de nom de
        // classe Eloquent vers le client.
        $exceptions->render(fn (ModelNotFoundException $exception, Request $request) => $wantsJson($request)
            ? $error('Ressource introuvable.', 'not_found', 404)
            : null);

        $exceptions->render(fn (NotFoundHttpException $exception, Request $request) => $wantsJson($request)
            ? $error('Ressource introuvable.', 'not_found', 404)
            : null);

        $exceptions->render(fn (ThrottleRequestsException $exception, Request $request) => $wantsJson($request)
            ? $error(
                'Trop de tentatives. Patientez avant de réessayer.',
                'too_many_requests',
                429,
                ['retry_after_seconds' => (int) ($exception->getHeaders()['Retry-After'] ?? 0)],
            )->withHeaders($exception->getHeaders())
            : null);
    })->create();
