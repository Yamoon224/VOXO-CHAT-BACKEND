<?php

namespace Tests\Feature\Auth;

use App\Domains\Notifications\Enums\MailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function une_adresse_connue_recoit_un_lien_utilisable(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/password/forgot', ['email' => $user->email])->assertNoContent();

        $message = $this->mailbox()->lastTo($user->email, MailTemplate::PasswordReset);
        $this->assertNotNull($message);
        $token = $message->data['action_url'];
        $token = (string) str($token)->after('token=')->before('&');

        $this->postJson('/api/v1/auth/password/reset', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertNoContent();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'nouveau-mot-de-passe'])
            ->assertOk();
    }

    /** Pas d'oracle d'énumération : réponse identique, adresse connue ou non. */
    #[Test]
    public function une_adresse_inconnue_recoit_la_meme_reponse(): void
    {
        $this->postJson('/api/v1/auth/password/forgot', ['email' => 'inconnu@example.test'])
            ->assertNoContent();
    }

    #[Test]
    public function un_jeton_invalide_est_refuse(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/password/reset', [
            'email' => $user->email,
            'token' => 'faux-jeton',
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertStatus(422)->assertJsonPath('error_code', 'invalid_reset_token');
    }
}
