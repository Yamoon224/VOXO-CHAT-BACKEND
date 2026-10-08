<?php

namespace Tests\Unit\Workspaces;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Exceptions\WorkspaceScopeViolationException;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Workspaces\Services\WorkspaceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\FakeAccessTokenManager;
use Tests\Support\Fakes\ImmediateTransactionManager;
use Tests\Support\Fakes\InMemoryMembershipRepository;
use Tests\Support\Fakes\InMemoryWorkspaceRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class WorkspaceServiceTest extends TestCase
{
    private InMemoryWorkspaceRepository $workspaces;

    private InMemoryMembershipRepository $memberships;

    private FakeAccessTokenManager $tokens;

    private WorkspaceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspaces = new InMemoryWorkspaceRepository;
        $this->memberships = new InMemoryMembershipRepository;
        $this->tokens = new FakeAccessTokenManager;
        $this->service = new WorkspaceService($this->workspaces, $this->memberships, $this->tokens, new ImmediateTransactionManager);
    }

    #[Test]
    public function provisionner_cree_l_espace_et_en_fait_le_proprietaire(): void
    {
        $member = $this->service->provision('user-1', 'Boutique Awa');

        $this->assertSame(WorkspaceRole::Owner, $member->role);
        $this->assertSame('Boutique Awa', $member->workspace->name);
        $this->assertSame('boutique-awa', $member->workspace->slug);
    }

    /**
     * Deux espaces de même nom ne doivent pas se voir attribuer le même slug
     * public, sous peine de collision d'URL (`/help/{slug}`).
     */
    #[Test]
    public function un_nom_deja_pris_produit_un_slug_different(): void
    {
        $first = $this->service->provision('user-1', 'Acme');
        $second = $this->service->provision('user-2', 'Acme');

        $this->assertNotSame($first->workspace->slug, $second->workspace->slug);
        $this->assertStringStartsWith('acme', $second->workspace->slug);
    }

    #[Test]
    public function un_nom_sans_caractere_exploitable_retombe_sur_un_slug_neutre(): void
    {
        $member = $this->service->provision('user-1', '!!!');

        $this->assertSame('espace', $member->workspace->slug);
    }

    #[Test]
    public function basculer_vers_un_espace_dont_on_est_membre_lie_le_jeton(): void
    {
        $workspace = $this->workspaces->create(['name' => 'Acme', 'slug' => 'acme']);
        $this->memberships->add($workspace->id, 'user-1', WorkspaceRole::Agent);

        $member = $this->service->switchTo(ModelFactory::user(['id' => 'user-1']), $workspace->id);

        $this->assertSame($workspace->id, $this->tokens->currentWorkspaceId);
        $this->assertSame($workspace->id, $member->workspace_id);
    }

    #[Test]
    public function basculer_vers_un_espace_etranger_est_refuse(): void
    {
        $workspace = $this->workspaces->create(['name' => 'Acme', 'slug' => 'acme']);

        $this->expectException(WorkspaceScopeViolationException::class);

        $this->service->switchTo(ModelFactory::user(['id' => 'user-1']), $workspace->id);
    }

    #[Test]
    public function mettre_a_jour_l_espace_courant_passe_par_son_perimetre(): void
    {
        $workspace = $this->workspaces->create(['name' => 'Acme', 'slug' => 'acme']);
        $scope = new WorkspaceScope($workspace->id, 'member-1', 'user-1', WorkspaceRole::Owner);

        $updated = $this->service->update($scope, ['name' => 'Acme Corp']);

        $this->assertSame('Acme Corp', $updated->name);
    }
}
