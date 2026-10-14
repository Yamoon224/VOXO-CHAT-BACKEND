<?php

namespace Tests\Unit\HelpCenter;

use App\Domains\HelpCenter\Services\HelpCenterCategoryService;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Support\WorkspaceScope;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\InMemoryHelpCenterCategoryRepository;
use Tests\Support\Fakes\ModelFactory;
use Tests\TestCase;

class HelpCenterCategoryServiceTest extends TestCase
{
    #[Test]
    public function creer_une_categorie_derive_son_slug_du_nom(): void
    {
        $service = new HelpCenterCategoryService(new InMemoryHelpCenterCategoryRepository);
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);

        $category = $service->create($scope, 'Facturation et paiements', 'fr', 0);

        $this->assertSame('facturation-et-paiements', $category->slug);
    }

    #[Test]
    public function un_slug_deja_pris_recoit_un_suffixe(): void
    {
        $existing = ModelFactory::helpCenterCategory(['workspace_id' => 'workspace-1', 'locale' => 'fr', 'slug' => 'facturation']);
        $service = new HelpCenterCategoryService(new InMemoryHelpCenterCategoryRepository($existing));
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);

        $category = $service->create($scope, 'Facturation', 'fr', 0);

        $this->assertSame('facturation-1', $category->slug);
    }

    #[Test]
    public function modifier_le_nom_change_le_slug(): void
    {
        $existing = ModelFactory::helpCenterCategory(['workspace_id' => 'workspace-1', 'locale' => 'fr', 'name' => 'Ancien nom', 'slug' => 'ancien-nom']);
        $service = new HelpCenterCategoryService(new InMemoryHelpCenterCategoryRepository($existing));
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);

        $updated = $service->update($scope, $existing->id, 'Nouveau nom', 2);

        $this->assertSame('nouveau-nom', $updated->slug);
        $this->assertSame(2, $updated->position);
    }

    #[Test]
    public function les_categories_sont_triees_par_position(): void
    {
        $second = ModelFactory::helpCenterCategory(['workspace_id' => 'workspace-1', 'position' => 1]);
        $first = ModelFactory::helpCenterCategory(['workspace_id' => 'workspace-1', 'position' => 0]);
        $service = new HelpCenterCategoryService(new InMemoryHelpCenterCategoryRepository($second, $first));
        $scope = new WorkspaceScope('workspace-1', 'member-1', 'user-1', WorkspaceRole::Admin);

        $categories = $service->list($scope);

        $this->assertSame([$first->id, $second->id], $categories->map(fn ($category) => $category->id)->all());
    }
}
