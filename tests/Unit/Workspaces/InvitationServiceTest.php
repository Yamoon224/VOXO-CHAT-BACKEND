<?php

namespace Tests\Unit\Workspaces;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use App\Domains\Workspaces\Exceptions\AccountDetailsRequiredException;
use App\Domains\Workspaces\Exceptions\AlreadyMemberException;
use App\Domains\Workspaces\Exceptions\InvitationEmailMismatchException;
use App\Domains\Workspaces\Exceptions\InvitationExpiredException;
use App\Domains\Workspaces\Exceptions\InvitationInvalidException;
use App\Domains\Workspaces\Exceptions\InvitationRequiresLoginException;
use App\Domains\Workspaces\Exceptions\RoleNotAssignableException;
use App\Domains\Workspaces\Services\InvitationService;
use App\Domains\Workspaces\Support\InvitationToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\FakeAccessTokenManager;
use Tests\Support\Fakes\ImmediateTransactionManager;
use Tests\Support\Fakes\InMemoryInvitationRepository;
use Tests\Support\Fakes\InMemoryMembershipRepository;
use Tests\Support\Fakes\InMemoryUserRepository;
use Tests\Support\Fakes\InMemoryWorkspaceRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\Support\Fakes\RecordingMailer;
use Tests\TestCase;

class InvitationServiceTest extends TestCase
{
    private InMemoryInvitationRepository $invitations;

    private InMemoryMembershipRepository $memberships;

    private InMemoryUserRepository $users;

    private RecordingMailer $mailer;

    private FakeAccessTokenManager $tokens;

    private InvitationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invitations = new InMemoryInvitationRepository;
        $this->memberships = new InMemoryMembershipRepository;
        $this->users = new InMemoryUserRepository;
        $this->mailer = new RecordingMailer;
        $this->tokens = new FakeAccessTokenManager;

        $workspace = ModelFactory::workspace('Acme', 'acme');
        $workspaces = new InMemoryWorkspaceRepository($workspace);

        $this->service = new InvitationService(
            $this->invitations,
            $this->memberships,
            $workspaces,
            $this->users,
            $this->tokens,
            $this->mailer,
            new ImmediateTransactionManager,
            168,
        );

        $this->workspaceId = $workspace->id;
    }

    private string $workspaceId;

    private function scope(): WorkspaceScope
    {
        return new WorkspaceScope($this->workspaceId, 'member-inviter', 'user-inviter', WorkspaceRole::Admin);
    }

    #[Test]
    public function inviter_envoie_un_e_mail_avec_un_jeton_verifiable(): void
    {
        $inviter = ModelFactory::user(['name' => 'Awa']);

        $invitation = $this->service->invite($this->scope(), $inviter, 'Nouveau@Example.test', WorkspaceRole::Agent);

        $this->assertSame('nouveau@example.test', $invitation->email);
        $this->assertSame('workspace_invitation', $this->mailer->sent[0]['type']);
        $resolved = $this->service->resolve((string) $this->mailer->lastToken());
        $this->assertSame($invitation->id, $resolved->id);
    }

    #[Test]
    public function le_role_de_proprietaire_ne_peut_pas_etre_distribue_par_invitation(): void
    {
        $this->expectException(RoleNotAssignableException::class);

        $this->service->invite($this->scope(), ModelFactory::user(), 'x@example.test', WorkspaceRole::Owner);
    }

    #[Test]
    public function on_ne_peut_pas_inviter_une_adresse_deja_membre(): void
    {
        $existing = $this->users->create(['name' => 'Déjà', 'email' => 'deja@example.test', 'password' => 'x']);
        $this->memberships->add($this->workspaceId, $existing->id, WorkspaceRole::Viewer);

        $this->expectException(AlreadyMemberException::class);

        $this->service->invite($this->scope(), ModelFactory::user(), 'deja@example.test', WorkspaceRole::Agent);
    }

    #[Test]
    public function un_jeton_inconnu_est_refuse(): void
    {
        $this->expectException(InvitationInvalidException::class);

        $this->service->resolve('jeton-invente');
    }

    #[Test]
    public function une_invitation_expiree_est_refusee(): void
    {
        $token = InvitationToken::generate();
        $this->invitations->issue(
            $this->workspaceId,
            'x@example.test',
            WorkspaceRole::Agent,
            InvitationToken::hash($token),
            'user-inviter',
            now()->subMinute(),
        );

        $this->expectException(InvitationExpiredException::class);

        $this->service->resolve($token);
    }

    #[Test]
    public function un_invite_sans_compte_en_cree_un_et_recoit_une_session(): void
    {
        $inviter = ModelFactory::user(['name' => 'Awa']);
        $this->service->invite($this->scope(), $inviter, 'nouveau@example.test', WorkspaceRole::Viewer);
        $token = (string) $this->mailer->lastToken();

        $accepted = $this->service->accept($token, null, ['name' => 'Nouveau', 'password' => 'motdepasse'], 'web');

        $this->assertNotNull($accepted->token);
        $this->assertSame(WorkspaceRole::Viewer, $accepted->member->role);
        $this->assertNotNull($this->users->findByEmail('nouveau@example.test'));
    }

    #[Test]
    public function creer_un_compte_exige_un_nom_et_un_mot_de_passe(): void
    {
        $this->service->invite($this->scope(), ModelFactory::user(), 'nouveau@example.test', WorkspaceRole::Viewer);
        $token = (string) $this->mailer->lastToken();

        $this->expectException(AccountDetailsRequiredException::class);

        $this->service->accept($token, null, [], 'web');
    }

    #[Test]
    public function une_adresse_qui_a_deja_un_compte_exige_une_connexion(): void
    {
        $this->users->create(['name' => 'Existant', 'email' => 'existant@example.test', 'password' => 'x']);
        $this->service->invite($this->scope(), ModelFactory::user(), 'existant@example.test', WorkspaceRole::Viewer);
        $token = (string) $this->mailer->lastToken();

        $this->expectException(InvitationRequiresLoginException::class);

        $this->service->accept($token, null, ['name' => 'X', 'password' => 'motdepasse'], 'web');
    }

    #[Test]
    public function un_compte_connecte_bascule_sur_l_espace_sans_nouveau_jeton(): void
    {
        $invitee = $this->users->create(['name' => 'Invite', 'email' => 'invite@example.test', 'password' => 'x']);
        $this->service->invite($this->scope(), ModelFactory::user(), 'invite@example.test', WorkspaceRole::Viewer);
        $token = (string) $this->mailer->lastToken();

        $accepted = $this->service->accept($token, $invitee, [], 'web');

        $this->assertNull($accepted->token);
        $this->assertSame($this->workspaceId, $this->tokens->currentWorkspaceId);
    }

    #[Test]
    public function un_compte_connecte_avec_la_mauvaise_adresse_est_refuse(): void
    {
        $this->service->invite($this->scope(), ModelFactory::user(), 'invite@example.test', WorkspaceRole::Viewer);
        $token = (string) $this->mailer->lastToken();

        $this->expectException(InvitationEmailMismatchException::class);

        $this->service->accept($token, ModelFactory::user(['email' => 'autre@example.test']), [], 'web');
    }
}
