<?php

namespace Tests\Unit\Workspaces;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Workspaces\Exceptions\OwnerProtectedException;
use App\Domains\Workspaces\Exceptions\RoleNotAssignableException;
use App\Domains\Workspaces\Exceptions\SelfMembershipChangeException;
use App\Domains\Workspaces\Services\MemberService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryMembershipRepository;
use Tests\TestCase;

class MemberServiceTest extends TestCase
{
    private const WORKSPACE_ID = 'workspace-a';

    private InMemoryMembershipRepository $memberships;

    private MemberService $service;

    private function scopeAs(string $memberId, WorkspaceRole $role = WorkspaceRole::Admin): WorkspaceScope
    {
        return new WorkspaceScope(self::WORKSPACE_ID, $memberId, 'user-'.$memberId, $role);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->memberships = new InMemoryMembershipRepository;
        $this->service = new MemberService($this->memberships);
    }

    #[Test]
    public function on_change_le_role_d_un_autre_membre(): void
    {
        $actor = $this->memberships->add(self::WORKSPACE_ID, 'user-actor', WorkspaceRole::Admin);
        $target = $this->memberships->add(self::WORKSPACE_ID, 'user-target', WorkspaceRole::Viewer);

        $updated = $this->service->changeRole($this->scopeAs($actor->id), $target->id, WorkspaceRole::Agent);

        $this->assertSame(WorkspaceRole::Agent, $updated->role);
    }

    #[Test]
    public function le_role_de_proprietaire_ne_peut_pas_etre_attribue(): void
    {
        $actor = $this->memberships->add(self::WORKSPACE_ID, 'user-actor', WorkspaceRole::Admin);
        $target = $this->memberships->add(self::WORKSPACE_ID, 'user-target', WorkspaceRole::Viewer);

        $this->expectException(RoleNotAssignableException::class);

        $this->service->changeRole($this->scopeAs($actor->id), $target->id, WorkspaceRole::Owner);
    }

    #[Test]
    public function on_ne_modifie_pas_sa_propre_adhesion(): void
    {
        $actor = $this->memberships->add(self::WORKSPACE_ID, 'user-actor', WorkspaceRole::Admin);

        $this->expectException(SelfMembershipChangeException::class);

        $this->service->changeRole($this->scopeAs($actor->id), $actor->id, WorkspaceRole::Viewer);
    }

    #[Test]
    public function le_proprietaire_ne_peut_etre_ni_retrograde_ni_retire(): void
    {
        $actor = $this->memberships->add(self::WORKSPACE_ID, 'user-actor', WorkspaceRole::Admin);
        $owner = $this->memberships->add(self::WORKSPACE_ID, 'user-owner', WorkspaceRole::Owner);

        try {
            $this->service->changeRole($this->scopeAs($actor->id), $owner->id, WorkspaceRole::Agent);
            $this->fail('Le propriétaire aurait dû être protégé.');
        } catch (OwnerProtectedException) {
        }

        $this->expectException(OwnerProtectedException::class);
        $this->service->remove($this->scopeAs($actor->id), $owner->id);
    }

    #[Test]
    public function retirer_un_membre_le_fait_disparaitre_de_l_equipe(): void
    {
        $actor = $this->memberships->add(self::WORKSPACE_ID, 'user-actor', WorkspaceRole::Admin);
        $target = $this->memberships->add(self::WORKSPACE_ID, 'user-target', WorkspaceRole::Viewer);

        $this->service->remove($this->scopeAs($actor->id), $target->id);

        $this->assertSame(1, $this->memberships->count());
    }
}
