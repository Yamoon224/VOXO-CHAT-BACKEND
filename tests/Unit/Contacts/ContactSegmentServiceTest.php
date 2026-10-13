<?php

namespace Tests\Unit\Contacts;

use App\Domains\Contacts\Services\ContactSegmentService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryContactRepository;
use Tests\Support\Fakes\InMemoryContactSegmentRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class ContactSegmentServiceTest extends TestCase
{
    #[Test]
    public function un_critere_is_set_filtre_les_contacts_sans_e_mail(): void
    {
        $withEmail = ModelFactory::contact(['workspace_id' => 'workspace-1', 'email' => 'awa@example.test']);
        $withoutEmail = ModelFactory::contact(['workspace_id' => 'workspace-1', 'email' => null]);
        $contacts = new InMemoryContactRepository($withEmail, $withoutEmail);

        $segments = new InMemoryContactSegmentRepository;
        $service = new ContactSegmentService($segments, $contacts);
        $segment = $service->create('workspace-1', [
            'name' => 'Avec e-mail',
            'criteria' => [['field' => 'email', 'operator' => 'is_set', 'value' => null]],
        ]);

        $matching = $service->matching('workspace-1', $segment->id);

        $this->assertCount(1, $matching);
        $this->assertSame($withEmail->id, $matching->first()->id);
    }

    #[Test]
    public function un_critere_contains_filtre_sur_un_attribut_personnalise(): void
    {
        $paris = ModelFactory::contact(['workspace_id' => 'workspace-1', 'custom_attributes' => ['ville' => 'Paris']]);
        $lyon = ModelFactory::contact(['workspace_id' => 'workspace-1', 'custom_attributes' => ['ville' => 'Lyon']]);
        $contacts = new InMemoryContactRepository($paris, $lyon);

        $segments = new InMemoryContactSegmentRepository;
        $service = new ContactSegmentService($segments, $contacts);
        $segment = $service->create('workspace-1', [
            'name' => 'Parisiens',
            'criteria' => [['field' => 'ville', 'operator' => 'contains', 'value' => 'par']],
        ]);

        $matching = $service->matching('workspace-1', $segment->id);

        $this->assertCount(1, $matching);
        $this->assertSame($paris->id, $matching->first()->id);
    }

    #[Test]
    public function plusieurs_criteres_se_combinent_en_et(): void
    {
        $match = ModelFactory::contact([
            'workspace_id' => 'workspace-1', 'email' => 'awa@example.test', 'custom_attributes' => ['plan' => 'business'],
        ]);
        $onlyEmail = ModelFactory::contact([
            'workspace_id' => 'workspace-1', 'email' => 'koffi@example.test', 'custom_attributes' => ['plan' => 'free'],
        ]);
        $contacts = new InMemoryContactRepository($match, $onlyEmail);

        $segments = new InMemoryContactSegmentRepository;
        $service = new ContactSegmentService($segments, $contacts);
        $segment = $service->create('workspace-1', [
            'name' => 'Business avec e-mail',
            'criteria' => [
                ['field' => 'email', 'operator' => 'is_set', 'value' => null],
                ['field' => 'plan', 'operator' => 'equals', 'value' => 'business'],
            ],
        ]);

        $matching = $service->matching('workspace-1', $segment->id);

        $this->assertCount(1, $matching);
        $this->assertSame($match->id, $matching->first()->id);
    }
}
