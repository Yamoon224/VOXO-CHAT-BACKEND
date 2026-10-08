<?php

namespace App\Domains\Auth\Http\Controllers;

use App\Domains\Auth\Http\Requests\ForgotPasswordRequest;
use App\Domains\Auth\Http\Requests\ResetPasswordRequest;
use App\Domains\Auth\Services\PasswordResetService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class PasswordResetController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwords) {}

    /** Répond 204 que l'adresse soit connue ou non. */
    public function forgot(ForgotPasswordRequest $request): Response
    {
        $this->passwords->requestLink($request->string('email')->toString());

        return response()->noContent();
    }

    public function reset(ResetPasswordRequest $request): Response
    {
        $this->passwords->reset(
            $request->string('email')->toString(),
            $request->string('token')->toString(),
            $request->string('password')->toString(),
        );

        return response()->noContent();
    }
}
