<?php

namespace App\Domains\Auth\Http\Controllers;

use App\Domains\Auth\Http\Requests\ConfirmTwoFactorRequest;
use App\Domains\Auth\Http\Requests\DisableTwoFactorRequest;
use App\Domains\Auth\Services\TwoFactorService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Activation et désactivation de la double authentification du compte
 * connecté.
 */
class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    /** Sans effet sur la connexion tant que `confirm` n'a pas reçu un premier code valide. */
    public function enable(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->twoFactor->startEnrollment($request->user())]);
    }

    public function confirm(ConfirmTwoFactorRequest $request): JsonResponse
    {
        $this->twoFactor->confirmEnrollment($request->user(), $request->string('code')->toString());

        return response()->json(['data' => ['enabled' => true]]);
    }

    public function disable(DisableTwoFactorRequest $request): JsonResponse
    {
        $this->twoFactor->disable($request->user(), $request->string('password')->toString());

        return response()->json(['data' => ['enabled' => false]]);
    }
}
