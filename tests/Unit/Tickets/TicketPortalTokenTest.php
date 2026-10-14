<?php

namespace Tests\Unit\Tickets;

use App\Domains\Tickets\Support\TicketPortalToken;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketPortalTokenTest extends TestCase
{
    #[Test]
    public function un_jeton_valide_renvoie_l_espace_et_le_contact_qui_l_ont_emis(): void
    {
        $token = new TicketPortalToken('clé-de-test', 90);
        $now = Carbon::now();

        $issued = $token->issue('workspace-1', 'contact-1', $now);

        $this->assertSame(['workspaceId' => 'workspace-1', 'contactId' => 'contact-1'], $token->verify($issued, $now));
    }

    #[Test]
    public function un_jeton_falsifie_est_refuse(): void
    {
        $token = new TicketPortalToken('clé-de-test', 90);
        $now = Carbon::now();

        $issued = $token->issue('workspace-1', 'contact-1', $now);
        $tampered = substr($issued, 0, -1).($issued[-1] === 'a' ? 'b' : 'a');

        $this->assertNull($token->verify($tampered, $now));
    }

    #[Test]
    public function un_jeton_expire_est_refuse(): void
    {
        $token = new TicketPortalToken('clé-de-test', 90);
        $issued = $token->issue('workspace-1', 'contact-1', Carbon::now()->subDays(91));

        $this->assertNull($token->verify($issued, Carbon::now()));
    }

    #[Test]
    public function un_jeton_signe_par_une_autre_cle_est_refuse(): void
    {
        $token = new TicketPortalToken('clé-a', 90);
        $other = new TicketPortalToken('clé-b', 90);
        $now = Carbon::now();

        $issued = $token->issue('workspace-1', 'contact-1', $now);

        $this->assertNull($other->verify($issued, $now));
    }
}
