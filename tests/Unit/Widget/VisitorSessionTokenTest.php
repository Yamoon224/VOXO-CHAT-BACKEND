<?php

namespace Tests\Unit\Widget;

use App\Domains\Widget\Support\VisitorSessionToken;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VisitorSessionTokenTest extends TestCase
{
    #[Test]
    public function un_jeton_valide_renvoie_l_espace_et_le_visiteur_qui_l_ont_emis(): void
    {
        $token = new VisitorSessionToken('clé-de-test', 60);
        $now = Carbon::now();

        $issued = $token->issue('workspace-1', 'visitor-1', $now);

        $this->assertSame(['workspaceId' => 'workspace-1', 'visitorId' => 'visitor-1'], $token->verify($issued, $now));
    }

    #[Test]
    public function un_jeton_falsifie_est_refuse(): void
    {
        $token = new VisitorSessionToken('clé-de-test', 60);
        $now = Carbon::now();

        $issued = $token->issue('workspace-1', 'visitor-1', $now);
        $tampered = substr($issued, 0, -1).($issued[-1] === 'a' ? 'b' : 'a');

        $this->assertNull($token->verify($tampered, $now));
    }

    #[Test]
    public function un_jeton_expire_est_refuse(): void
    {
        $token = new VisitorSessionToken('clé-de-test', 60);
        $issued = $token->issue('workspace-1', 'visitor-1', Carbon::now()->subHours(2));

        $this->assertNull($token->verify($issued, Carbon::now()));
    }

    #[Test]
    public function un_jeton_signe_par_une_autre_cle_est_refuse(): void
    {
        $token = new VisitorSessionToken('clé-a', 60);
        $other = new VisitorSessionToken('clé-b', 60);
        $now = Carbon::now();

        $issued = $token->issue('workspace-1', 'visitor-1', $now);

        $this->assertNull($other->verify($issued, $now));
    }
}
