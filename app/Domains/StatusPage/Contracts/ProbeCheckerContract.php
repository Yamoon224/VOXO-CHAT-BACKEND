<?php

namespace App\Domains\StatusPage\Contracts;

use App\Domains\StatusPage\DTOs\ProbeCheckResult;

/** Pilote de vérification HTTP, choisi par configuration (section 2.11) — même forme que `PaymentGatewayContract`. */
interface ProbeCheckerContract
{
    public function check(string $url): ProbeCheckResult;
}
