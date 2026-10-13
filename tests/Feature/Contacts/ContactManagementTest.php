<?php

namespace Tests\Feature\Contacts;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Contact;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_agent_cree_et_consulte_un_contact(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $response = $this->actingInWorkspace($agent, $workspace)
            ->postJson('/api/v1/workspace/contacts', ['name' => 'Awa Koné', 'email' => 'awa@example.test'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Awa Koné');

        $contactId = $response->json('data.id');

        $this->actingInWorkspace($agent, $workspace)
            ->getJson("/api/v1/workspace/contacts/{$contactId}")
            ->assertOk()
            ->assertJsonPath('data.email', 'awa@example.test');
    }

    #[Test]
    public function creer_un_contact_avec_un_e_mail_deja_pris_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        Contact::factory()->create(['workspace_id' => $workspace->id, 'email' => 'awa@example.test']);

        $this->actingInWorkspace($agent, $workspace)
            ->postJson('/api/v1/workspace/contacts', ['name' => 'Doublon', 'email' => 'awa@example.test'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'contact_email_taken');
    }

    #[Test]
    public function un_viewer_ne_peut_pas_creer_de_contact(): void
    {
        $workspace = Workspace::factory()->create();
        $viewer = $this->memberOf($workspace, WorkspaceRole::Viewer);

        $this->actingInWorkspace($viewer, $workspace)
            ->postJson('/api/v1/workspace/contacts', ['name' => 'Refusé'])
            ->assertStatus(403);
    }

    #[Test]
    public function un_viewer_peut_consulter_la_liste(): void
    {
        $workspace = Workspace::factory()->create();
        $viewer = $this->memberOf($workspace, WorkspaceRole::Viewer);
        Contact::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Awa']);

        $this->actingInWorkspace($viewer, $workspace)
            ->getJson('/api/v1/workspace/contacts')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Awa']);
    }

    #[Test]
    public function modifier_un_contact(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $contact = Contact::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Ancien nom']);

        $this->actingInWorkspace($agent, $workspace)
            ->putJson("/api/v1/workspace/contacts/{$contact->id}", ['name' => 'Nouveau nom'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nouveau nom');
    }

    #[Test]
    public function supprimer_un_contact(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $contact = Contact::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($agent, $workspace)
            ->deleteJson("/api/v1/workspace/contacts/{$contact->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    #[Test]
    public function les_contacts_d_un_espace_n_apparaissent_pas_dans_un_autre(): void
    {
        $workspace = Workspace::factory()->create();
        $otherWorkspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $foreignContact = Contact::factory()->create(['workspace_id' => $otherWorkspace->id]);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson("/api/v1/workspace/contacts/{$foreignContact->id}")
            ->assertStatus(404);
    }
}
