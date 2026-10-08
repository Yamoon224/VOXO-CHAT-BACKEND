<?php

namespace Tests\Unit\Conversations;

use App\Domains\Conversations\Exceptions\CannedResponseTitleTakenException;
use App\Domains\Conversations\Services\CannedResponseService;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryCannedResponseRepository;
use Tests\TestCase;

class CannedResponseServiceTest extends TestCase
{
    private function scope(): WorkspaceScope
    {
        return new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Agent);
    }

    #[Test]
    public function deux_reponses_du_meme_titre_sont_refusees(): void
    {
        $service = new CannedResponseService(new InMemoryCannedResponseRepository);

        $service->create($this->scope(), 'Bienvenue', 'Bonjour et bienvenue !');

        $this->expectException(CannedResponseTitleTakenException::class);

        $service->create($this->scope(), 'Bienvenue', 'Un autre message.');
    }

    #[Test]
    public function modifier_une_reponse_sans_changer_son_titre_est_permis(): void
    {
        $service = new CannedResponseService(new InMemoryCannedResponseRepository);

        $response = $service->create($this->scope(), 'Bienvenue', 'Bonjour !');
        $updated = $service->update($this->scope(), $response->id, 'Bienvenue', 'Bonjour et merci de nous écrire !');

        $this->assertSame('Bonjour et merci de nous écrire !', $updated->body);
    }
}
