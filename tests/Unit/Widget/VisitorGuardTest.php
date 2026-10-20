<?php

namespace Tests\Unit\Widget;

use App\Domains\Widget\Support\VisitorPrincipal;
use App\Domains\Widget\Support\VisitorSessionToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Garde `visitor` (section 4.1), enregistré via `Auth::viaRequest()` dans
 * `DomainServiceProvider::boot()` — reconstruit une identité anonyme à
 * partir du jeton de session porté par la requête d'authentification de
 * canal (`visitor_token`), jamais stockée.
 */
class VisitorGuardTest extends TestCase
{
    #[Test]
    public function un_jeton_visiteur_valide_resout_un_principal(): void
    {
        $token = app(VisitorSessionToken::class)->issue('workspace-1', 'visitor-1', now());
        $this->app->instance('request', Request::create('/broadcasting/auth', 'POST', ['visitor_token' => $token]));

        $user = Auth::guard('visitor')->user();

        $this->assertInstanceOf(VisitorPrincipal::class, $user);
        $this->assertSame('workspace-1', $user->workspaceId);
        $this->assertSame('visitor-1', $user->visitorId);
    }

    #[Test]
    public function un_jeton_visiteur_porte_par_l_en_tete_resout_aussi_un_principal(): void
    {
        $token = app(VisitorSessionToken::class)->issue('workspace-1', 'visitor-1', now());
        $request = Request::create('/broadcasting/auth', 'POST');
        $request->headers->set('X-Visitor-Token', $token);
        $this->app->instance('request', $request);

        $user = Auth::guard('visitor')->user();

        $this->assertInstanceOf(VisitorPrincipal::class, $user);
        $this->assertSame('workspace-1', $user->workspaceId);
    }

    #[Test]
    public function un_jeton_visiteur_invalide_ne_resout_personne(): void
    {
        $this->app->instance('request', Request::create('/broadcasting/auth', 'POST', ['visitor_token' => 'falsifie']));

        $this->assertNull(Auth::guard('visitor')->user());
    }

    #[Test]
    public function l_absence_de_jeton_ne_resout_personne(): void
    {
        $this->app->instance('request', Request::create('/broadcasting/auth', 'POST'));

        $this->assertNull(Auth::guard('visitor')->user());
    }
}
