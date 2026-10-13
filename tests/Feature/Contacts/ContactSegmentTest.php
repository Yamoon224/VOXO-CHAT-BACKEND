<?php

namespace Tests\Feature\Contacts;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Contact;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactSegmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function creer_un_segment_et_lister_ses_contacts(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        Contact::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Avec e-mail', 'email' => 'awa@example.test']);
        Contact::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Sans e-mail', 'email' => null]);

        $response = $this->actingInWorkspace($agent, $workspace)
            ->postJson('/api/v1/workspace/contact-segments', [
                'name' => 'Avec adresse connue',
                'criteria' => [['field' => 'email', 'operator' => 'is_set']],
            ])
            ->assertCreated();

        $segmentId = $response->json('data.id');

        $this->actingInWorkspace($agent, $workspace)
            ->getJson("/api/v1/workspace/contact-segments/{$segmentId}/contacts")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Avec e-mail');
    }

    #[Test]
    public function supprimer_un_segment(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $response = $this->actingInWorkspace($agent, $workspace)
            ->postJson('/api/v1/workspace/contact-segments', [
                'name' => 'À supprimer',
                'criteria' => [['field' => 'email', 'operator' => 'is_set']],
            ])
            ->assertCreated();

        $segmentId = $response->json('data.id');

        $this->actingInWorkspace($agent, $workspace)
            ->deleteJson("/api/v1/workspace/contact-segments/{$segmentId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('contact_segments', ['id' => $segmentId]);
    }
}
