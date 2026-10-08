<?php

namespace Tests\Feature\Auth;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function l_inscription_cree_un_compte_un_espace_et_ouvre_une_session(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Awa Koné',
            'email' => 'Awa@Example.test',
            'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide',
            'workspace_name' => 'Boutique Awa',
            'device_name' => 'navigateur-test',
        ]);

        $response->assertCreated()->assertJsonStructure([
            'data' => ['token', 'session' => ['user' => ['id', 'email'], 'workspace' => ['id', 'role'], 'permissions']],
        ]);

        $this->assertSame('awa@example.test', $response->json('data.session.user.email'));
        $this->assertSame('workspace_owner', $response->json('data.session.workspace.role'));
        $this->assertFalse($response->json('data.session.user.email_verified'));

        $user = User::where('email', 'awa@example.test')->firstOrFail();
        $this->assertDatabaseHas('workspace_members', ['user_id' => $user->id, 'role' => WorkspaceRole::Owner->value]);
        $this->assertSame('array', config('notifications.mail.driver'));
    }

    #[Test]
    public function le_mot_de_passe_doit_etre_confirme(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Awa',
            'email' => 'awa@example.test',
            'password' => 'motdepasse-solide',
            'password_confirmation' => 'autre-chose',
            'workspace_name' => 'Boutique',
        ])->assertStatus(422)->assertJsonPath('error_code', 'validation_failed');
    }

    #[Test]
    public function une_adresse_deja_utilisee_est_refusee(): void
    {
        User::factory()->create(['email' => 'awa@example.test']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Awa',
            'email' => 'awa@example.test',
            'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide',
            'workspace_name' => 'Boutique',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    /** Deux inscriptions concurrentes avec le même nom d'espace ne doivent pas se percuter. */
    #[Test]
    public function deux_espaces_de_meme_nom_obtiennent_des_slugs_distincts(): void
    {
        $first = $this->postJson('/api/v1/auth/register', [
            'name' => 'A', 'email' => 'a@example.test', 'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide', 'workspace_name' => 'Acme',
        ]);
        $second = $this->postJson('/api/v1/auth/register', [
            'name' => 'B', 'email' => 'b@example.test', 'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide', 'workspace_name' => 'Acme',
        ]);

        $this->assertNotSame(
            $first->json('data.session.workspace.slug'),
            $second->json('data.session.workspace.slug'),
        );
    }

    #[Test]
    public function l_inscription_est_limitee_en_debit(): void
    {
        $payload = fn (int $i) => [
            'name' => 'Awa', 'email' => "awa{$i}@example.test", 'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide', 'workspace_name' => 'Boutique',
        ];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/register', $payload($i));
        }

        $this->postJson('/api/v1/auth/register', $payload(99))
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'too_many_requests');
    }
}
