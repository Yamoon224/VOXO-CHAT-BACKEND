<?php

namespace App\Domains\Auth\Http\Controllers;

use App\Domains\Auth\Http\Requests\VerifyEmailRequest;
use App\Domains\Auth\Services\EmailVerificationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly EmailVerificationService $verification) {}

    /**
     * Route publique : le lien s'ouvre souvent sur un autre appareil que celui
     * de l'inscription, où aucune session n'existe.
     */
    public function verify(VerifyEmailRequest $request): JsonResponse
    {
        $user = $this->verification->verify($request->string('token')->toString());

        return response()->json(['data' => ['email' => $user->email, 'email_verified' => true]]);
    }

    public function resend(Request $request): Response
    {
        $this->verification->send($request->user());

        return response()->noContent();
    }
}
