<?php

namespace App\Domains\Payments\Http\Controllers;

use App\Domains\Payments\Services\WebhookProcessor;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Point d'entrée public des rappels du prestataire de paiement : pas de jeton
 * Sanctum, l'authenticité tient à la signature vérifiée dans
 * `PaymentGatewayContract::parseWebhookEvent()`.
 */
class WebhookController extends Controller
{
    public function __construct(private readonly WebhookProcessor $processor) {}

    public function store(Request $request): Response
    {
        $this->processor->process($request->getContent(), $request->header('Stripe-Signature'));

        return response()->noContent();
    }
}
