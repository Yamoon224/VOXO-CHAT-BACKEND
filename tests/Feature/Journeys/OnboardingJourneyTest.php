<?php

namespace Tests\Feature\Journeys;

use App\Domains\Notifications\Enums\MailTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Parcours de bout en bout, à travers plusieurs domaines : une entreprise
 * s'inscrit, confirme son adresse, invite un coéquipier, qui rejoint l'équipe
 * et travaille dans l'espace qui lui a été ouvert — sans jamais voir l'espace
 * d'une autre entreprise.
 */
class OnboardingJourneyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function une_entreprise_s_inscrit_confirme_son_adresse_et_accueille_un_coequipier(): void
    {
        // 1. Inscription : compte, espace de travail, session ouverte.
        $registration = $this->postJson('/api/v1/auth/register', [
            'name' => 'Awa Koné',
            'email' => 'awa@example.test',
            'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide',
            'workspace_name' => 'Acme Support',
        ])->assertCreated();

        $ownerToken = $registration->json('data.token');
        $workspaceId = $registration->json('data.session.workspace.id');
        $this->assertFalse($registration->json('data.session.user.email_verified'));

        // 2. Confirmation de l'adresse à partir du lien reçu.
        $verificationToken = (string) str(
            $this->mailbox()->lastTo('awa@example.test', MailTemplate::EmailVerification)->data['action_url'],
        )->after('token=');

        $this->postJson('/api/v1/auth/email/verify', ['token' => $verificationToken])
            ->assertOk()->assertJsonPath('data.email_verified', true);

        $this->assertTrue(
            $this->withToken($ownerToken)->getJson('/api/v1/auth/me')->assertOk()->json('data.user.email_verified'),
        );

        // 3. Elle invite un coéquipier.
        $this->withToken($ownerToken)
            ->postJson('/api/v1/workspace/invitations', ['email' => 'kofi@example.test', 'role' => 'agent'])
            ->assertCreated();

        $invitationToken = (string) str(
            $this->mailbox()->lastTo('kofi@example.test', MailTemplate::WorkspaceInvitation)->data['action_url'],
        )->after('token=');

        // 4. Le coéquipier accepte et crée son propre compte dans la foulée.
        $accepted = $this->asGuest()->postJson("/api/v1/invitations/{$invitationToken}/accept", [
            'name' => 'Kofi',
            'password' => 'autre-mot-de-passe',
            'password_confirmation' => 'autre-mot-de-passe',
        ])->assertOk();

        $teammateToken = $accepted->json('data.token');
        $this->assertSame($workspaceId, $accepted->json('data.workspace_id'));

        // 5. Le coéquipier voit l'équipe de cet espace, et seulement celui-ci.
        $members = $this->withToken($teammateToken)->getJson('/api/v1/workspace/members')->assertOk();
        $this->assertCount(2, $members->json('data'));

        // 6. Un agent ne peut pas, lui, gérer l'équipe.
        $this->withToken($teammateToken)
            ->postJson('/api/v1/workspace/invitations', ['email' => 'autre@example.test', 'role' => 'viewer'])
            ->assertStatus(403);
    }
}
