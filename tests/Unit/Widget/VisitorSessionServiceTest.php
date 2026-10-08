<?php

namespace Tests\Unit\Widget;

use App\Domains\Widget\Exceptions\VisitorSessionInvalidException;
use App\Domains\Widget\Services\VisitorSessionService;
use App\Domains\Widget\Support\VisitorSessionToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VisitorSessionServiceTest extends TestCase
{
    #[Test]
    public function demarrer_une_session_emet_un_jeton_verifiable(): void
    {
        $service = new VisitorSessionService(new VisitorSessionToken('clé-de-test', 60));

        $session = $service->start('workspace-1');

        $claims = $service->verify($session['token']);

        $this->assertSame('workspace-1', $claims['workspaceId']);
        $this->assertSame($session['visitor_id'], $claims['visitorId']);
    }

    #[Test]
    public function un_jeton_invalide_leve_une_exception_de_domaine(): void
    {
        $service = new VisitorSessionService(new VisitorSessionToken('clé-de-test', 60));

        $this->expectException(VisitorSessionInvalidException::class);

        $service->verify('jeton-invalide');
    }
}
