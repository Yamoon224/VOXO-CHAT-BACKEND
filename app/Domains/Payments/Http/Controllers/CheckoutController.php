<?php

namespace App\Domains\Payments\Http\Controllers;

use App\Domains\Payments\Http\Requests\StartCheckoutRequest;
use App\Domains\Payments\Services\CheckoutService;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkout) {}

    public function store(StartCheckoutRequest $request): JsonResponse
    {
        $user = $request->user();

        $checkoutUrl = $this->checkout->startCheckout(
            WorkspaceScope::fromRequest($request),
            $request->string('plan_slug')->toString(),
            $user->email,
            $user->name,
        );

        return response()->json(['checkout_url' => $checkoutUrl]);
    }
}
