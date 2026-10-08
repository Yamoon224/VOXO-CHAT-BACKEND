<?php

use App\Domains\Auth\Http\Controllers\AuthController;
use App\Domains\Auth\Http\Controllers\EmailVerificationController;
use App\Domains\Auth\Http\Controllers\PasswordResetController;
use App\Domains\Auth\Http\Controllers\TwoFactorController;
use App\Domains\Knowledge\Http\Controllers\KnowledgeDocumentController;
use App\Domains\Knowledge\Http\Controllers\KnowledgeQaEntryController;
use App\Domains\Knowledge\Http\Controllers\KnowledgeSearchController;
use App\Domains\Knowledge\Http\Controllers\KnowledgeSourceController;
use App\Domains\Knowledge\Http\Controllers\KnowledgeUploadController;
use App\Domains\Knowledge\Http\Controllers\KnowledgeWebsiteSourceController;
use App\Domains\Shared\Http\Controllers\HealthController;
use App\Domains\Users\Http\Controllers\ProfileController;
use App\Domains\Workspaces\Http\Controllers\InvitationAcceptanceController;
use App\Domains\Workspaces\Http\Controllers\InvitationController;
use App\Domains\Workspaces\Http\Controllers\MemberController;
use App\Domains\Workspaces\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST — préfixe /api/v1
|--------------------------------------------------------------------------
|
| Conventions : ressources au pluriel, verbes HTTP porteurs de l'intention,
| 201 sur création, 204 sans corps, 422 sur validation, 409 sur conflit
| d'état métier.
|
| Trois zones :
|
|   1. Publique : inscription, connexion, liens reçus par e-mail. Limitée en
|      débit.
|   2. Compte : tout ce qui concerne l'appelant lui-même, quel que soit
|      l'espace de travail (`auth:sanctum`).
|   3. Espace de travail : le périmètre vient du jeton (`workspace`), jamais
|      de l'URL ni du corps. C'est pourquoi la ressource s'écrit `/workspace`
|      au singulier : il n'y a pas d'identifiant à fournir, donc pas
|      d'identifiant à falsifier.
|
*/

Route::get('/health', HealthController::class);

// =============================================================================
// Zone publique
// =============================================================================

Route::middleware('throttle:10,1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/email/verify', [EmailVerificationController::class, 'verify']);
    Route::post('/auth/password/forgot', [PasswordResetController::class, 'forgot']);
    Route::post('/auth/password/reset', [PasswordResetController::class, 'reset']);
});

Route::middleware('throttle:30,1')->group(function (): void {
    Route::get('/invitations/{token}', [InvitationAcceptanceController::class, 'show']);
    Route::post('/invitations/{token}/accept', [InvitationAcceptanceController::class, 'accept']);
});

// =============================================================================
// Zone compte
// =============================================================================

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1');

    Route::post('/auth/two-factor', [TwoFactorController::class, 'enable']);
    Route::post('/auth/two-factor/confirm', [TwoFactorController::class, 'confirm']);
    Route::delete('/auth/two-factor', [TwoFactorController::class, 'disable']);

    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::post('/workspaces', [WorkspaceController::class, 'store']);
    Route::post('/workspaces/{workspace}/switch', [WorkspaceController::class, 'switch'])->whereUuid('workspace');

    // =========================================================================
    // Zone espace de travail
    // =========================================================================

    Route::middleware('workspace')->prefix('workspace')->group(function (): void {
        Route::get('/', [WorkspaceController::class, 'show'])->middleware('workspace.can:workspace.view');
        Route::put('/', [WorkspaceController::class, 'update'])->middleware('workspace.can:workspace.manage');

        Route::get('/members', [MemberController::class, 'index'])->middleware('workspace.can:members.view');

        Route::middleware('workspace.can:members.manage')->group(function (): void {
            Route::put('/members/{member}', [MemberController::class, 'update'])->whereUuid('member');
            Route::delete('/members/{member}', [MemberController::class, 'destroy'])->whereUuid('member');

            Route::get('/invitations', [InvitationController::class, 'index']);
            Route::post('/invitations', [InvitationController::class, 'store']);
            Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->whereUuid('invitation');
        });

        // =====================================================================
        // Base de connaissances (lot 1)
        // =====================================================================

        Route::middleware('workspace.can:knowledge.view')->group(function (): void {
            Route::get('/knowledge/sources', [KnowledgeSourceController::class, 'index']);
            Route::get('/knowledge/documents', [KnowledgeDocumentController::class, 'index']);
            Route::get('/knowledge/documents/{document}', [KnowledgeDocumentController::class, 'show'])->whereUuid('document');
            Route::get('/knowledge/qa-entries', [KnowledgeQaEntryController::class, 'index']);
            Route::post('/knowledge/search', [KnowledgeSearchController::class, 'store']);
        });

        Route::middleware('workspace.can:knowledge.manage')->group(function (): void {
            Route::post('/knowledge/uploads', [KnowledgeUploadController::class, 'store']);
            Route::post('/knowledge/websites', [KnowledgeWebsiteSourceController::class, 'store']);
            Route::delete('/knowledge/sources/{source}', [KnowledgeSourceController::class, 'destroy'])->whereUuid('source');
            Route::post('/knowledge/sources/{source}/recrawl', [KnowledgeSourceController::class, 'recrawl'])->whereUuid('source');
            Route::delete('/knowledge/documents/{document}', [KnowledgeDocumentController::class, 'destroy'])->whereUuid('document');
            Route::post('/knowledge/documents/{document}/retry', [KnowledgeDocumentController::class, 'retry'])->whereUuid('document');
            Route::post('/knowledge/qa-entries', [KnowledgeQaEntryController::class, 'store']);
            Route::put('/knowledge/qa-entries/{document}', [KnowledgeQaEntryController::class, 'update'])->whereUuid('document');
            Route::delete('/knowledge/qa-entries/{document}', [KnowledgeQaEntryController::class, 'destroy'])->whereUuid('document');
        });
    });
});
