<?php

use App\Domains\StatusPage\Providers\ArrayProbeChecker;
use App\Domains\StatusPage\Providers\HttpProbeChecker;

/*
|--------------------------------------------------------------------------
| Page de statut (lot 7, section 2.11)
|--------------------------------------------------------------------------
|
| Le domaine ne connaît que `ProbeCheckerContract` : le pilote de
| vérification se choisit ici, comme pour les paiements (`config/payments.php`).
|
*/

return [

    'probe_checker' => [
        'driver' => env('STATUS_PAGE_PROBE_CHECKER_DRIVER', 'http'),

        'drivers' => [
            'http' => HttpProbeChecker::class,
            // Doublure sans appel réseau : pilote de la suite de tests.
            'array' => ArrayProbeChecker::class,
        ],
    ],

];
