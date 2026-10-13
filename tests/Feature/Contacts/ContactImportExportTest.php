<?php

namespace Tests\Feature\Contacts;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\Contact;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactImportExportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function importer_un_csv_cree_et_met_a_jour_des_contacts(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        Contact::factory()->create(['workspace_id' => $workspace->id, 'email' => 'koffi@example.test', 'name' => 'Ancien nom']);

        $csv = "name,email,phone,ville\n"
            ."Awa Koné,awa@example.test,0600000000,Paris\n"
            ."Koffi Nouveau,koffi@example.test,,Lyon\n";

        $this->actingInWorkspace($agent, $workspace)
            ->post('/api/v1/workspace/contacts/import', [
                'file' => UploadedFile::fake()->createWithContent('contacts.csv', $csv),
            ])
            ->assertOk()
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.updated', 1);

        $this->assertDatabaseHas('contacts', ['workspace_id' => $workspace->id, 'email' => 'awa@example.test', 'name' => 'Awa Koné']);
        $this->assertDatabaseHas('contacts', ['workspace_id' => $workspace->id, 'email' => 'koffi@example.test', 'name' => 'Koffi Nouveau']);
    }

    #[Test]
    public function importer_un_csv_sans_colonne_e_mail_est_refuse(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);

        $this->actingInWorkspace($agent, $workspace)
            ->post('/api/v1/workspace/contacts/import', [
                'file' => UploadedFile::fake()->createWithContent('contacts.csv', "name\nAwa\n"),
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'contact_import_missing_email_column');
    }

    #[Test]
    public function exporter_les_contacts_en_csv(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = $this->memberOf($workspace, WorkspaceRole::Agent);
        Contact::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Awa Koné', 'email' => 'awa@example.test']);

        $response = $this->actingInWorkspace($agent, $workspace)->get('/api/v1/workspace/contacts/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Awa Koné', $response->getContent());
        $this->assertStringContainsString('awa@example.test', $response->getContent());
    }
}
