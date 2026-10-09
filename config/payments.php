<?php

use App\Domains\Payments\Gateways\ArrayPaymentGateway;
use App\Domains\Payments\Gateways\StripePaymentGateway;

/*
|--------------------------------------------------------------------------
| Paiements (lot 3)
|--------------------------------------------------------------------------
|
| Le domaine ne connaît que `PaymentGatewayContract` : le prestataire se
| choisit ici. Stripe d'abord, un autre prestataire ou un agrégateur local
| plus tard sans changer les domaines appelants (section 2.13).
|
*/

return [

    'driver' => env('PAYMENTS_GATEWAY_DRIVER', 'stripe'),

    'drivers' => [
        'stripe' => StripePaymentGateway::class,
        // Doublure sans appel réseau : pilote de la suite de tests.
        'array' => ArrayPaymentGateway::class,
    ],

    'stripe' => [
        'key' => env('STRIPE_API_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
