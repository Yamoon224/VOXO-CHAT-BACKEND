<?php

/*
|--------------------------------------------------------------------------
| Widget de chat (lot 2)
|--------------------------------------------------------------------------
*/

return [

    // Domaine servant le bundle `widget.js` (pas encore livré, voir la note
    // de portée du lot 2) : seul le texte du script est généré pour l'instant.
    'script_base_url' => env('WIDGET_SCRIPT_BASE_URL', env('FRONTEND_URL', 'http://localhost:3000')),

    'visitor_session' => [
        // ~180 jours : un visiteur qui revient retrouve sa conversation.
        'ttl_minutes' => (int) env('WIDGET_VISITOR_SESSION_TTL_MINUTES', 259200),
    ],

];
