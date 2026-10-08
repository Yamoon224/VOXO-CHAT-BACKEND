<?php

namespace Tests\Unit\Auth;

use App\Domains\Auth\Exceptions\AccountDisabledException;
use App\Domains\Auth\Exceptions\InvalidCredentialsException;
use App\Domains\Auth\Exceptions\InvalidTwoFactorCodeException;
use App\Domains\Auth\Exceptions\TwoFactorRequiredException;
use App\Domains\Auth\Services\AuthService;
use App\Domains\Auth\Services\TwoFactorService;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\FakeAccessTokenManager;
use Tests\Support\Fakes\FakeTotpProvider;
use Tests\Support\Fakes\InMemoryMembershipRepository;
use Tests\Support\Fakes\InMemoryUserRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\Support\Fakes\PlainHasher;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    private FakeAccessTokenManager $tokens;

    private InMemoryMembershipRepository $memberships;

    private InMemoryUserRepository $users;

    private function service(User ...$users): AuthService
    {
        $this->users = new InMemoryUserRepository(...$users);
        $this->memberships ??= new InMemoryMembershipRepository;
        $this->tokens = new FakeAccessTokenManager;

        return new AuthService(
            $this->users,
            $this->memberships,
            $this->tokens,
            new TwoFactorService(new FakeTotpProvider, $this->users, new PlainHasher),
            new PlainHasher,
        );
    }

    #[Test]
    public function la_session_s_ouvre_sur_le_premier_espace_du_compte(): void
    {
        $user = ModelFactory::user();
        $this->memberships = new InMemoryMembershipRepository(
            ModelFactory::member('workspace-a', $user->id, WorkspaceRole::Owner),
            ModelFactory::member('workspace-b', $user->id, WorkspaceRole::Agent),
        );

        $session = $this->service($user)->attempt('AWA@example.test', 'password', null, 'web');

        $this->assertSame('workspace-a', $session->workspaceId);
        $this->assertSame([['user_id' => $user->id, 'workspace_id' => 'workspace-a', 'device' => 'web']], $this->tokens->issued);
        $this->assertNotNull($session->user->last_login_at);
    }

    #[Test]
    public function un_compte_sans_espace_obtient_une_session_sans_perimetre(): void
    {
        $session = $this->service(ModelFactory::user())->attempt('awa@example.test', 'password', null, 'web');

        $this->assertNull($session->workspaceId);
    }

    /** Même erreur que le compte existe ou non : pas d'oracle d'énumération. */
    #[Test]
    public function un_mot_de_passe_faux_et_un_compte_inconnu_donnent_la_meme_erreur(): void
    {
        $service = $this->service(ModelFactory::user());

        foreach ([['awa@example.test', 'faux'], ['inconnu@example.test', 'password']] as [$email, $password]) {
            try {
                $service->attempt($email, $password, null, 'web');
                $this->fail('La connexion aurait dû être refusée.');
            } catch (InvalidCredentialsException $exception) {
                $this->assertSame('invalid_credentials', $exception->errorCode());
            }
        }

        $this->assertSame([], $this->tokens->issued);
    }

    #[Test]
    public function un_compte_desactive_ne_se_connecte_pas(): void
    {
        $this->expectException(AccountDisabledException::class);

        $this->service(ModelFactory::user(['is_active' => false]))->attempt('awa@example.test', 'password', null, 'web');
    }

    #[Test]
    public function la_double_authentification_exige_un_code_puis_le_verifie(): void
    {
        $user = ModelFactory::user(['two_factor_confirmed_at' => '2026-01-01 00:00:00']);
        $user->two_factor_secret = FakeTotpProvider::SECRET;
        $service = $this->service($user);

        try {
            $service->attempt('awa@example.test', 'password', null, 'web');
            $this->fail('Un code aurait dû être exigé.');
        } catch (TwoFactorRequiredException) {
            $this->assertCount(0, $this->tokens->issued);
        }

        try {
            $service->attempt('awa@example.test', 'password', '000000', 'web');
            $this->fail('Le code aurait dû être refusé.');
        } catch (InvalidTwoFactorCodeException) {
            $this->assertCount(0, $this->tokens->issued);
        }

        $service->attempt('awa@example.test', 'password', FakeTotpProvider::VALID_CODE, 'web');
        $this->assertCount(1, $this->tokens->issued);
    }

    #[Test]
    public function la_deconnexion_revoque_le_jeton_courant(): void
    {
        $user = ModelFactory::user();

        $this->service($user)->logout($user);

        $this->assertTrue($this->tokens->currentRevoked);
    }
}
