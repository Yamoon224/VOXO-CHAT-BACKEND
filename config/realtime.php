<?php

use App\Domains\Realtime\Providers\ArrayConversationBroadcaster;
use App\Domains\Realtime\Services\EventConversationBroadcaster;

/*
|--------------------------------------------------------------------------
| Temps réel (lot 2, section 4.1)
|--------------------------------------------------------------------------
|
| `event` passe par le système d'évènements de Laravel (donc par le pilote
| de diffusion configuré dans `config/broadcasting.php`, Reverb en
| production) ; `array` ne diffuse rien, pilote de la suite de tests.
|
*/

return [

    'broadcaster' => [
        'driver' => env('REALTIME_BROADCASTER_DRIVER', 'event'),

        'drivers' => [
            'event' => EventConversationBroadcaster::class,
            'array' => ArrayConversationBroadcaster::class,
        ],
    ],

];
