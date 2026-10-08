<?php

namespace Tests\Unit\Shared;

use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Exceptions\PermissionDeniedException;
use App\Domains\Shared\Support\PageSize;
use App\Domains\Shared\Support\Sort;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SharedSupportTest extends TestCase
{
    #[Test]
    #[DataProvider('pageSizes')]
    public function la_taille_de_page_est_bornee_cote_serveur(mixed $requested, int $expected): void
    {
        $this->assertSame($expected, PageSize::from($requested));
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function pageSizes(): iterable
    {
        yield 'absente' => [null, PageSize::DEFAULT];
        yield 'illisible' => ['beaucoup', PageSize::DEFAULT];
        yield 'raisonnable' => ['40', 40];
        yield 'excessive' => [100000, PageSize::MAX];
        yield 'nulle' => [0, 1];
        yield 'negative' => [-5, 1];
    }

    /** La direction n'atteint le SQL que sous la forme de deux mots-clés. */
    #[Test]
    public function la_direction_de_tri_est_bornee_a_deux_valeurs(): void
    {
        $this->assertSame('desc', Sort::direction(['direction' => 'DESC']));
        $this->assertSame('asc', Sort::direction(['direction' => 'asc']));
        $this->assertSame('asc', Sort::direction(['direction' => 'desc; drop table users']));
        $this->assertSame('asc', Sort::direction(['direction' => ['desc']]));
        $this->assertSame('asc', Sort::direction([]));
    }

    #[Test]
    public function le_role_de_proprietaire_ne_s_attribue_pas(): void
    {
        $this->assertFalse(WorkspaceRole::Owner->isAssignable());
        $this->assertSame(['workspace_admin', 'agent', 'viewer'], WorkspaceRole::assignableValues());
        $this->assertSame(['workspace_owner', 'workspace_admin', 'agent', 'viewer'], WorkspaceRole::values());
    }

    #[Test]
    public function chaque_role_a_un_libelle(): void
    {
        foreach (WorkspaceRole::cases() as $role) {
            $this->assertNotSame('', $role->label());
        }
    }

    #[Test]
    public function une_erreur_metier_porte_son_code_http_et_son_code_applicatif(): void
    {
        $exception = PermissionDeniedException::forPermission('members.manage');

        $this->assertSame(403, $exception->statusCode());
        $this->assertSame('forbidden', $exception->errorCode());
        $this->assertSame(['required_permission' => 'members.manage'], $exception->context());
    }
}
