<?php

namespace Tests\Unit\Auth;

use App\Domains\Auth\Exceptions\InvalidPasswordException;
use App\Domains\Auth\Exceptions\InvalidResetTokenException;
use App\Domains\Auth\Exceptions\InvalidTwoFactorCodeException;
use App\Domains\Auth\Exceptions\InvalidVerificationTokenException;
use App\Domains\Auth\Exceptions\TwoFactorNotStartedException;
use App\Domains\Auth\Services\EmailVerificationService;
use App\Domains\Auth\Services\PasswordResetService;
use App\Domains\Auth\Services\RegistrationService;
use App\Domains\Auth\Services\TwoFactorService;
use App\Domains\Auth\Support\EmailVerificationToken;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Workspaces\Services\WorkspaceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\FakeAccessTokenManager;
use Tests\Support\Fakes\FakeTotpProvider;
use Tests\Support\Fakes\ImmediateTransactionManager;
use Tests\Support\Fakes\InMemoryMembershipRepository;
use Tests\Support\Fakes\InMemoryPasswordResetTokenStore;
use Tests\Support\Fakes\InMemorySubscriptionProvisioner;
use Tests\Support\Fakes\InMemoryUserRepository;
use Tests\Support\Fakes\InMemoryWorkspaceRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\Support\Fakes\PlainHasher;
use Tests\Support\Fakes\RecordingMailer;
use Tests\TestCase;

/**
 * Inscription, vérification d'adresse, mot de passe oublié et double
 * authentification, sans base : les contrats sont remplacés par des doublures.
 */
class AccountServicesTest extends TestCase
{
    private InMemoryUserRepository $users;

    private RecordingMailer $mailer;

    private FakeAccessTokenManager $tokens;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = new InMemoryUserRepository;
        $this->mailer = new RecordingMailer;
        $this->tokens = new FakeAccessTokenManager;
    }

    private function verification(): EmailVerificationService
    {
        return new EmailVerificationService(new EmailVerificationToken('key', 60), $this->users, $this->mailer);
    }

    // --- Inscription ---------------------------------------------------------

    #[Test]
    public function l_inscription_cree_le_compte_son_espace_et_ouvre_la_session_dessus(): void
    {
        $memberships = new InMemoryMembershipRepository;
        $registration = new RegistrationService(
            $this->users,
            new WorkspaceService(new InMemoryWorkspaceRepository, $memberships, $this->tokens, new ImmediateTransactionManager, new InMemorySubscriptionProvisioner),
            $this->tokens,
            $this->verification(),
            new ImmediateTransactionManager,
        );

        $session = $registration->register([
            'name' => 'Awa Koné',
            'email' => 'Awa@Example.test',
            'password' => 'motdepasse',
            'workspace_name' => 'Boutique Awa',
        ], 'web');

        $membership = $memberships->forUser($session->user->id)->first();

        $this->assertSame('awa@example.test', $session->user->email);
        $this->assertNotNull($membership);
        $this->assertSame(WorkspaceRole::Owner, $membership->role);
        $this->assertSame($membership->workspace_id, $session->workspaceId);
        $this->assertSame($session->workspaceId, $this->tokens->issued[0]['workspace_id']);
        $this->assertSame('email_verification', $this->mailer->sent[0]['type']);
        $this->assertFalse($session->user->hasVerifiedEmail());
    }

    // --- Vérification d'adresse ----------------------------------------------

    #[Test]
    public function le_lien_recu_verifie_l_adresse_et_peut_etre_rejoue(): void
    {
        $user = $this->users->create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => 'x']);
        $service = $this->verification();

        $service->send($user);
        $token = (string) $this->mailer->lastToken();

        $this->assertTrue($service->verify($token)->hasVerifiedEmail());
        $this->assertTrue($service->verify($token)->hasVerifiedEmail());
    }

    #[Test]
    public function aucun_e_mail_ne_part_pour_une_adresse_deja_verifiee(): void
    {
        $user = $this->users->create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => 'x'], emailVerified: true);

        $this->verification()->send($user);

        $this->assertSame([], $this->mailer->sent);
    }

    #[Test]
    public function un_lien_devient_invalide_si_l_adresse_du_compte_a_change(): void
    {
        $user = $this->users->create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => 'x']);
        $service = $this->verification();
        $service->send($user);
        $this->users->update($user, ['email' => 'nouvelle@example.test']);

        $this->expectException(InvalidVerificationTokenException::class);

        $service->verify((string) $this->mailer->lastToken());
    }

    #[Test]
    public function un_jeton_quelconque_est_refuse(): void
    {
        $this->expectException(InvalidVerificationTokenException::class);

        $this->verification()->verify('pas-un-jeton');
    }

    // --- Mot de passe oublié -------------------------------------------------

    private function passwords(InMemoryPasswordResetTokenStore $store): PasswordResetService
    {
        return new PasswordResetService($this->users, $store, $this->tokens, $this->mailer);
    }

    #[Test]
    public function la_reinitialisation_change_le_mot_de_passe_et_ferme_toutes_les_sessions(): void
    {
        $user = $this->users->create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => 'ancien']);
        $store = new InMemoryPasswordResetTokenStore;
        $service = $this->passwords($store);

        $service->requestLink('awa@example.test');
        $token = (string) $this->mailer->lastToken();
        $service->reset('awa@example.test', $token, 'nouveau-mot-de-passe');

        $this->assertSame('nouveau-mot-de-passe', $user->getAttributes()['password']);
        $this->assertSame([$user->id], $this->tokens->revokedAllFor);
        // Le jeton est à usage unique.
        $this->assertFalse($store->isValid($user, $token));
    }

    /** Aucune différence observable entre une adresse connue et une inconnue. */
    #[Test]
    public function une_adresse_inconnue_ou_un_compte_desactive_ne_recoit_rien(): void
    {
        $this->users = new InMemoryUserRepository(ModelFactory::user(['email' => 'off@example.test', 'is_active' => false]));
        $service = $this->passwords(new InMemoryPasswordResetTokenStore);

        $service->requestLink('inconnu@example.test');
        $service->requestLink('off@example.test');

        $this->assertSame([], $this->mailer->sent);
    }

    #[Test]
    public function une_seconde_demande_trop_rapprochee_n_envoie_pas_de_second_e_mail(): void
    {
        $this->users->create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => 'x']);
        $store = new InMemoryPasswordResetTokenStore;
        $store->throttled = true;

        $this->passwords($store)->requestLink('awa@example.test');

        $this->assertSame([], $this->mailer->sent);
    }

    #[Test]
    public function un_jeton_de_reinitialisation_faux_est_refuse(): void
    {
        $this->users->create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => 'ancien']);

        $this->expectException(InvalidResetTokenException::class);

        $this->passwords(new InMemoryPasswordResetTokenStore)->reset('awa@example.test', 'faux', 'nouveau-mot-de-passe');
    }

    // --- Double authentification ---------------------------------------------

    private function twoFactor(): TwoFactorService
    {
        return new TwoFactorService(new FakeTotpProvider, $this->users, new PlainHasher);
    }

    #[Test]
    public function la_double_authentification_n_est_active_qu_apres_confirmation(): void
    {
        $user = $this->users->create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => 'password']);
        $service = $this->twoFactor();

        $enrollment = $service->startEnrollment($user);
        $this->assertSame(FakeTotpProvider::SECRET, $enrollment['secret']);
        $this->assertStringContainsString('awa@example.test', $enrollment['otpauth_url']);
        $this->assertFalse($user->hasTwoFactorEnabled());

        $service->confirmEnrollment($user, FakeTotpProvider::VALID_CODE);
        $this->assertTrue($user->hasTwoFactorEnabled());

        $service->disable($user, 'password');
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    #[Test]
    public function un_code_faux_ne_confirme_pas_l_activation(): void
    {
        $user = $this->users->create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => 'password']);
        $service = $this->twoFactor();
        $service->startEnrollment($user);

        try {
            $service->confirmEnrollment($user, '000000');
            $this->fail('Le code aurait dû être refusé.');
        } catch (InvalidTwoFactorCodeException) {
            $this->assertFalse($user->hasTwoFactorEnabled());
        }
    }

    #[Test]
    public function on_ne_confirme_pas_une_activation_jamais_demarree(): void
    {
        $user = $this->users->create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => 'password']);

        $this->expectException(TwoFactorNotStartedException::class);

        $this->twoFactor()->confirmEnrollment($user, FakeTotpProvider::VALID_CODE);
    }

    #[Test]
    public function la_desactivation_exige_le_mot_de_passe(): void
    {
        $user = $this->users->create(['name' => 'Awa', 'email' => 'awa@example.test', 'password' => 'password']);

        $this->expectException(InvalidPasswordException::class);

        $this->twoFactor()->disable($user, 'faux');
    }
}
