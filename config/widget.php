<?php

/*
|--------------------------------------------------------------------------
| Widget de chat (lot 2)
|--------------------------------------------------------------------------
*/

return [

    // Domaine servant le bundle `widget.js` (`web/public/widget.js`, section 2.3).
    'script_base_url' => env('WIDGET_SCRIPT_BASE_URL', env('FRONTEND_URL', 'http://localhost:3000')),

    // Origine de CETTE API, à inclure dans `window.VoxoWidgetConfig` : le
    // widget tourne sur le domaine du client, il ne peut pas la déduire lui-même.
    'api_base_url' => env('WIDGET_API_BASE_URL', rtrim((string) env('APP_URL', 'http://localhost:8000'), '/').'/api/v1'),

    'visitor_session' => [
        // ~180 jours : un visiteur qui revient retrouve sa conversation.
        'ttl_minutes' => (int) env('WIDGET_VISITOR_SESSION_TTL_MINUTES', 259200),
    ],

];
