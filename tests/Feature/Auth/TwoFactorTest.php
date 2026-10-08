<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function activer_puis_confirmer_active_la_double_authentification(): void
    {
        $user = User::factory()->create();
        $client = $this->actingInWorkspace($user, null);

        $enroll = $client->postJson('/api/v1/auth/two-factor')->assertOk();
        $secret = $enroll->json('data.secret');
        $this->assertFalse($user->refresh()->hasTwoFactorEnabled());

        $code = app(Google2FA::class)->getCurrentOtp($secret);
        $client->postJson('/api/v1/auth/two-factor/confirm', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('data.enabled', true);

        $this->assertTrue($user->refresh()->hasTwoFactorEnabled());
    }

    #[Test]
    public function un_code_errone_ne_confirme_pas_l_activation(): void
    {
        $user = User::factory()->create();
        $client = $this->actingInWorkspace($user, null);
        $client->postJson('/api/v1/auth/two-factor');

        $client->postJson('/api/v1/auth/two-factor/confirm', ['code' => '000000'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'invalid_two_factor_code');
    }

    #[Test]
    public function desactiver_exige_le_mot_de_passe(): void
    {
        $user = User::factory()->create();
        $client = $this->actingInWorkspace($user, null);
        $secret = $client->postJson('/api/v1/auth/two-factor')->json('data.secret');
        $client->postJson('/api/v1/auth/two-factor/confirm', ['code' => app(Google2FA::class)->getCurrentOtp($secret)]);

        $client->deleteJson('/api/v1/auth/two-factor', ['password' => 'faux'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'invalid_password');

        $client->deleteJson('/api/v1/auth/two-factor', ['password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.enabled', false);
    }
}
