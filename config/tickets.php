<?php

/*
|--------------------------------------------------------------------------
| Tickets (lot 4)
|--------------------------------------------------------------------------
*/

return [

    'portal' => [
        // Pas de compte portail (décision d'architecture du 2026-10-13) : ce
        // jeton, reçu une fois par e-mail, est le seul accès du client — une
        // durée longue plutôt qu'une session courte comme le widget.
        'ttl_days' => (int) env('TICKETS_PORTAL_TTL_DAYS', 90),
    ],

];
