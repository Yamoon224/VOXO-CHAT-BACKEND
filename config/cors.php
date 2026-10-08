<?php

/*
|--------------------------------------------------------------------------
| CORS
|--------------------------------------------------------------------------
|
| L'application web vit sur une autre origine que l'API. Les origines
| autorisées sont lues dans l'environnement : `*` serait inacceptable pour une
| API qui expose les conversations des clients de nos clients.
|
| L'API publique du widget, appelée depuis les sites des clients, aura sa
| propre validation d'origine par domaine autorisé (lot 2).
|
*/

$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:3000'))),
)));

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $origins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Retry-After'],

    'max_age' => 600,

    // Authentification par jeton Bearer, pas par cookie de session : aucune
    // raison d'autoriser l'envoi de credentials entre origines.
    'supports_credentials' => false,
];
