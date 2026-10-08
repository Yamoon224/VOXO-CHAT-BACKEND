<?php

namespace Tests\Feature\Auth;

use App\Domains\Auth\Services\TwoFactorService;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Workspaces\Services\WorkspaceService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_utilisateur_se_connecte_et_recoit_sa_session(): void
    {
        $workspace = app(WorkspaceService::class)->provision(
            User::factory()->create(['email' => 'owner@example.test'])->id,
            'Acme',
        )->workspace;
        $user = $this->memberOf($workspace, WorkspaceRole::Agent, User::factory()->create([
            'email' => 'agent@example.test',
        ]));

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'agent@example.test',
            'password' => 'password',
            'device_name' => 'tablette',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.session.user.id', $user->id)
            ->assertJsonPath('data.session.workspace.role', 'agent');

        $this->assertNotNull($user->refresh()->last_login_at);
    }

    /** Même message que le compte existe ou non : pas d'oracle d'énumération. */
    #[Test]
    public function des_identifiants_invalides_renvoient_le_meme_message(): void
    {
        $user = User::factory()->create(['email' => 'awa@example.test']);

        $wrongPassword = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'faux']);
        $unknownEmail = $this->postJson('/api/v1/auth/login', ['email' => 'inconnu@example.test', 'password' => 'faux']);

        $wrongPassword->assertStatus(422)->assertJsonPath('error_code', 'invalid_credentials');
        $this->assertSame($wrongPassword->json('message'), $unknownEmail->json('message'));
    }

    #[Test]
    public function un_compte_desactive_ne_peut_pas_se_connecter(): void
    {
        $user = User::factory()->inactive()->create();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'account_disabled');
    }

    #[Test]
    public function la_double_authentification_est_exigee_puis_verifiee(): void
    {
        $user = User::factory()->create();
        app(TwoFactorService::class)->startEnrollment($user);
        // Le test connaît le secret par un canal distinct de l'API : il en
        // dérive directement le code attendu, comme le ferait une application
        // d'authentification.
        $google2fa = app(Google2FA::class);
        $code = $google2fa->getCurrentOtp($user->refresh()->two_factor_secret);
        app(TwoFactorService::class)->confirmEnrollment($user->refresh(), $code);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'two_factor_required');

        $freshCode = $google2fa->getCurrentOtp($user->refresh()->two_factor_secret);
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password', 'code' => $freshCode])
            ->assertOk();
    }

    #[Test]
    public function la_deconnexion_revoque_le_jeton(): void
    {
        $user = User::factory()->create();

        $this->actingInWorkspace($user, null)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function une_route_protegee_repond_401_en_json_sans_jeton(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'unauthenticated');
    }

    #[Test]
    public function le_changement_de_mot_de_passe_exige_l_ancien(): void
    {
        $user = User::factory()->create();

        $this->actingInWorkspace($user, null)->putJson('/api/v1/profile/password', [
            'current_password' => 'faux',
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertStatus(422)->assertJsonPath('error_code', 'current_password_mismatch');

        $this->actingInWorkspace($user, null)->putJson('/api/v1/profile/password', [
            'current_password' => 'password',
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertNoContent();
    }
}
