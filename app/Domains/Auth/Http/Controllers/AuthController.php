<?php

namespace App\Domains\Auth\Http\Controllers;

use App\Domains\Auth\DTOs\IssuedSession;
use App\Domains\Auth\Http\Requests\LoginRequest;
use App\Domains\Auth\Http\Requests\RegisterRequest;
use App\Domains\Auth\Http\Resources\SessionResource;
use App\Domains\Auth\Services\AuthService;
use App\Domains\Auth\Services\RegistrationService;
use App\Domains\Auth\Services\SessionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly RegistrationService $registration,
        private readonly SessionService $sessions,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $session = $this->registration->register(
            $request->registrationData(),
            $request->string('device_name', 'web')->toString(),
        );

        return $this->issued($session, 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $session = $this->auth->attempt(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->filled('code') ? $request->string('code')->toString() : null,
            $request->string('device_name', 'web')->toString(),
        );

        return $this->issued($session, 200);
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout($request->user());

        return response()->noContent();
    }

    public function me(Request $request): SessionResource
    {
        return new SessionResource($this->sessions->current($request->user()));
    }

    private function issued(IssuedSession $session, int $status): JsonResponse
    {
        return response()->json([
            'data' => [
                'token' => $session->token,
                'session' => new SessionResource($this->sessions->describe($session->user, $session->workspaceId)),
            ],
        ], $status);
    }
}
