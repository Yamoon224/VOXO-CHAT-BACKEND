<?php

namespace Tests\Unit\Tickets;

use App\Domains\Tickets\Exceptions\TicketPortalTokenInvalidException;
use App\Domains\Tickets\Services\TicketPortalTokenService;
use App\Domains\Tickets\Support\TicketPortalToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketPortalTokenServiceTest extends TestCase
{
    #[Test]
    public function un_jeton_valide_renvoie_l_espace_et_le_contact(): void
    {
        $service = new TicketPortalTokenService(new TicketPortalToken('clé-de-test', 90));

        $issued = $service->issue('workspace-1', 'contact-1');

        $this->assertSame(['workspaceId' => 'workspace-1', 'contactId' => 'contact-1'], $service->verify($issued));
    }

    #[Test]
    public function un_jeton_invalide_leve_une_exception_de_domaine(): void
    {
        $service = new TicketPortalTokenService(new TicketPortalToken('clé-de-test', 90));

        $this->expectException(TicketPortalTokenInvalidException::class);

        $service->verify('jeton-invalide');
    }
}
