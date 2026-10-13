<?php

namespace Tests\Feature\Contacts;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Contact;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_agent_ajoute_une_note_et_elle_apparait_dans_l_historique(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        $contact = Contact::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($agent, $workspace)
            ->postJson("/api/v1/workspace/contacts/{$contact->id}/notes", ['body' => 'Client prioritaire.'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Client prioritaire.')
            ->assertJsonPath('data.author_name', $agent->name);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson("/api/v1/workspace/contacts/{$contact->id}/notes")
            ->assertOk()
            ->assertJsonFragment(['body' => 'Client prioritaire.']);

        $this->actingInWorkspace($agent, $workspace)
            ->getJson("/api/v1/workspace/contacts/{$contact->id}/events")
            ->assertOk()
            ->assertJsonFragment(['type' => 'note_added']);
    }

    #[Test]
    public function un_viewer_ne_peut_pas_ajouter_de_note(): void
    {
        $workspace = Workspace::factory()->create();
        $viewer = $this->memberOf($workspace, WorkspaceRole::Viewer);
        $contact = Contact::factory()->create(['workspace_id' => $workspace->id]);

        $this->actingInWorkspace($viewer, $workspace)
            ->postJson("/api/v1/workspace/contacts/{$contact->id}/notes", ['body' => 'Refusé'])
            ->assertStatus(403);
    }
}
