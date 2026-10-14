<?php

use App\Domains\Channels\Providers\ArrayEmailChannelProvider;
use App\Domains\Channels\Providers\LaravelMailEmailChannelProvider;

/*
|--------------------------------------------------------------------------
| Canaux (lot 4)
|--------------------------------------------------------------------------
|
| `Email` ne connaît que `EmailChannelProviderContract` : le pilote se
| choisit ici. `laravel` passe par le mailer déjà configuré (SMTP, section
| 2.15) ; un prestataire dédié à la réception entrante (Postmark, Mailgun...)
| pourra s'ajouter plus tard sans changer le domaine (section 2.5).
|
*/

return [

    'email' => [
        'driver' => env('CHANNELS_EMAIL_DRIVER', 'laravel'),

        'drivers' => [
            'laravel' => LaravelMailEmailChannelProvider::class,
            // Doublure sans envoi réel : pilote de la suite de tests.
            'array' => ArrayEmailChannelProvider::class,
        ],
    ],

];
