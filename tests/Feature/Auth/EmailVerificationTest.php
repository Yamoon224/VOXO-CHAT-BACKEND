<?php

namespace Tests\Feature\Auth;

use App\Domains\Auth\Services\EmailVerificationService;
use App\Domains\Notifications\Enums\MailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_lien_valide_verifie_l_adresse(): void
    {
        $user = User::factory()->unverified()->create();
        $token = $this->issueVerificationToken($user);

        $this->postJson('/api/v1/auth/email/verify', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('data.email_verified', true);

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    #[Test]
    public function un_jeton_invalide_est_refuse(): void
    {
        $this->postJson('/api/v1/auth/email/verify', ['token' => 'faux-jeton'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'invalid_verification_token');
    }

    #[Test]
    public function renvoyer_le_lien_exige_une_session(): void
    {
        $this->postJson('/api/v1/auth/email/resend')->assertStatus(401);
    }

    #[Test]
    public function un_utilisateur_connecte_peut_demander_un_nouveau_lien(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingInWorkspace($user, null)->postJson('/api/v1/auth/email/resend')->assertNoContent();

        $this->assertNotNull($this->mailbox()->lastTo(
            $user->email,
            MailTemplate::EmailVerification,
        ));
    }

    private function issueVerificationToken(User $user): string
    {
        app(EmailVerificationService::class)->send($user);

        $message = $this->mailbox()->lastTo($user->email, MailTemplate::EmailVerification);
        $this->assertNotNull($message);

        return (string) str($message->data['action_url'])->after('token=');
    }
}
