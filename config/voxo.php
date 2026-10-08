<?php

/*
|--------------------------------------------------------------------------
| Réglages métier de VOXO
|--------------------------------------------------------------------------
*/

return [
    // Origine de l'application web : sert à construire les liens des e-mails.
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),

    'invitations' => [
        // Une semaine : assez pour qu'un invité en congé retrouve le message.
        'ttl_hours' => (int) env('INVITATION_TTL_HOURS', 168),
    ],

    'email_verification' => [
        'ttl_minutes' => (int) env('EMAIL_VERIFICATION_TTL_MINUTES', 1440),
    ],

    'two_factor' => [
        // Nom affiché dans l'application d'authentification.
        'issuer' => env('TWO_FACTOR_ISSUER', 'VOXO'),
    ],
];
