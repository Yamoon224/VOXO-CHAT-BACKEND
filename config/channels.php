<?php

use App\Domains\Channels\Providers\ArrayEmailChannelProvider;
use App\Domains\Channels\Providers\ArrayInstagramChannelProvider;
use App\Domains\Channels\Providers\ArrayMessengerChannelProvider;
use App\Domains\Channels\Providers\ArraySmsChannelProvider;
use App\Domains\Channels\Providers\ArrayTelegramChannelProvider;
use App\Domains\Channels\Providers\ArrayWhatsAppChannelProvider;
use App\Domains\Channels\Providers\InstagramGraphApiProvider;
use App\Domains\Channels\Providers\LaravelMailEmailChannelProvider;
use App\Domains\Channels\Providers\MessengerGraphApiProvider;
use App\Domains\Channels\Providers\TelegramBotApiProvider;
use App\Domains\Channels\Providers\TwilioSmsProvider;
use App\Domains\Channels\Providers\WhatsAppCloudApiProvider;

/*
|--------------------------------------------------------------------------
| Canaux (lot 4, lot 5)
|--------------------------------------------------------------------------
|
| Chaque canal ne connaît que son propre contrat (`EmailChannelProviderContract`,
| `WhatsAppChannelProviderContract`...) : le pilote se choisit ici, sans
| changer le domaine `Channels` (section 2.5). `Email` passe par le mailer
| déjà configuré (SMTP) ; les canaux du lot 5 n'ont pas de transport partagé
| — chaque espace connecte son propre numéro/bot/page (`ChannelConnection`),
| le pilote HTTP par défaut appelle l'API du prestataire avec ces identifiants.
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

    'whatsapp' => [
        'driver' => env('CHANNELS_WHATSAPP_DRIVER', 'http'),

        'drivers' => [
            'http' => WhatsAppCloudApiProvider::class,
            'array' => ArrayWhatsAppChannelProvider::class,
        ],
    ],

    'messenger' => [
        'driver' => env('CHANNELS_MESSENGER_DRIVER', 'http'),

        'drivers' => [
            'http' => MessengerGraphApiProvider::class,
            'array' => ArrayMessengerChannelProvider::class,
        ],
    ],

    'instagram' => [
        'driver' => env('CHANNELS_INSTAGRAM_DRIVER', 'http'),

        'drivers' => [
            'http' => InstagramGraphApiProvider::class,
            'array' => ArrayInstagramChannelProvider::class,
        ],
    ],

    'telegram' => [
        'driver' => env('CHANNELS_TELEGRAM_DRIVER', 'http'),

        'drivers' => [
            'http' => TelegramBotApiProvider::class,
            'array' => ArrayTelegramChannelProvider::class,
        ],
    ],

    'sms' => [
        'driver' => env('CHANNELS_SMS_DRIVER', 'http'),

        'drivers' => [
            'http' => TwilioSmsProvider::class,
            'array' => ArraySmsChannelProvider::class,
        ],
    ],

];
