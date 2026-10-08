<?php

namespace Tests\Unit\Shared;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Exceptions\WorkspaceRequiredException;
use App\Domains\Shared\Exceptions\WorkspaceScopeViolationException;
use App\Domains\Shared\Support\WorkspaceScope;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class WorkspaceScopeTest extends TestCase
{
    private function scope(): WorkspaceScope
    {
        return new WorkspaceScope('workspace-a', 'member-1', 'user-1', WorkspaceRole::Agent);
    }

    /**
     * Le filtre de sécurité écrase ce que la requête aurait pu fournir : un
     * `workspace_id` choisi par le client ne franchit jamais ce point.
     */
    #[Test]
    public function il_impose_son_espace_aux_filtres_quoi_que_dise_la_requete(): void
    {
        $filters = $this->scope()->apply(['workspace_id' => 'workspace-b', 'search' => 'awa']);

        $this->assertSame(['workspace_id' => 'workspace-a', 'search' => 'awa'], $filters);
    }

    #[Test]
    public function il_ne_reconnait_que_son_propre_espace(): void
    {
        $this->assertTrue($this->scope()->owns('workspace-a'));
        $this->assertFalse($this->scope()->owns('workspace-b'));
        $this->assertFalse($this->scope()->owns(null));
    }

    #[Test]
    public function il_refuse_une_ressource_d_un_autre_espace(): void
    {
        $this->expectException(WorkspaceScopeViolationException::class);

        $this->scope()->assertOwns('workspace-b');
    }

    #[Test]
    public function il_se_transporte_par_la_requete(): void
    {
        $request = new Request;
        $this->scope()->attachTo($request);

        $this->assertEquals($this->scope(), WorkspaceScope::fromRequest($request));
    }

    #[Test]
    public function une_requete_sans_perimetre_resolu_est_refusee(): void
    {
        $this->expectException(WorkspaceRequiredException::class);

        WorkspaceScope::fromRequest(new Request);
    }
}
