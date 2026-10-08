<?php

use App\Domains\Assistant\Providers\ArrayAiProvider;
use App\Domains\Assistant\Providers\ClaudeAiProvider;

/*
|--------------------------------------------------------------------------
| Agent IA (lot 2)
|--------------------------------------------------------------------------
|
| Le domaine ne connaît que `AiProviderContract` : le fournisseur se choisit
| ici. Réutilise la clé Anthropic déjà déclarée pour l'OCR du domaine
| Knowledge (`ANTHROPIC_API_KEY`, `ANTHROPIC_BASE_URL`).
|
*/

return [

    'driver' => env('ASSISTANT_AI_DRIVER', 'claude'),

    'drivers' => [
        'claude' => ClaudeAiProvider::class,
        // Doublure sans appel réseau : pilote de la suite de tests.
        'array' => ArrayAiProvider::class,
    ],

    'claude' => [
        'key' => env('ANTHROPIC_API_KEY'),
        // Sonnet pour répondre, Haiku pour les tâches courtes (section 4.6).
        'respond_model' => env('ANTHROPIC_RESPOND_MODEL', 'claude-sonnet-5-5'),
        'short_task_model' => env('ANTHROPIC_SHORT_TASK_MODEL', 'claude-haiku-4-5'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
    ],

];
