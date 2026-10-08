<?php

use App\Domains\Notifications\Senders\ArrayMailSender;
use App\Domains\Notifications\Senders\LaravelMailSender;

/*
|--------------------------------------------------------------------------
| Notifications sortantes
|--------------------------------------------------------------------------
|
| Le domaine ne connaît que MailSenderContract : le prestataire se choisit ici.
|
*/

return [
    'mail' => [
        'driver' => env('VOXO_MAIL_DRIVER', 'laravel'),

        'drivers' => [
            // Mailer Laravel : le transport (SMTP, Resend, Mailgun, journal) se
            // règle par MAIL_MAILER dans config/mail.php.
            'laravel' => LaravelMailSender::class,
            // Conserve les envois en mémoire : pilote de la suite de tests.
            'array' => ArrayMailSender::class,
        ],
    ],
];
