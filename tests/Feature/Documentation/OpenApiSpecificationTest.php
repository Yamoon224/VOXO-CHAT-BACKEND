<?php

namespace Tests\Feature\Documentation;

use App\Domains\Billing\Enums\BillingInterval;
use App\Domains\Billing\Enums\SubscriptionStatus;
use App\Domains\Conversations\Enums\ConversationChannel;
use App\Domains\Conversations\Enums\ConversationStatus;
use App\Domains\Conversations\Enums\MessageSenderType;
use App\Domains\Conversations\Enums\MessageVisibility;
use App\Domains\Knowledge\Enums\KnowledgeDocumentStatus;
use App\Domains\Knowledge\Enums\KnowledgeDocumentType;
use App\Domains\Knowledge\Enums\KnowledgeSourceType;
use App\Domains\Knowledge\Enums\RecrawlFrequency;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Shared\Http\Controllers\DocumentationController;
use App\Domains\Widget\Enums\WidgetPosition;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La documentation de l'API est écrite à la main : ce test empêche qu'elle
 * dérive du code. Il échoue si une route exposée n'est pas documentée, si une
 * route documentée n'existe plus, ou si un enum documenté diverge de l'enum PHP.
 */
class OpenApiSpecificationTest extends TestCase
{
    /** @return list<string> « METHODE /chemin » tels que documentés, préfixe /api/v1 retiré */
    private function documentedOperations(): array
    {
        $spec = DocumentationController::specificationArray();
        $operations = [];

        foreach ($spec['paths'] as $path => $methods) {
            foreach (array_keys($methods) as $method) {
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    $operations[] = strtoupper($method).' '.$path;
                }
            }
        }

        sort($operations);

        return $operations;
    }

    /** @return list<string> « METHODE /chemin » tels qu'enregistrés, préfixe /api/v1 retiré */
    private function registeredOperations(): array
    {
        $operations = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }

            foreach ($route->methods() as $method) {
                if ($method === 'HEAD') {
                    continue;
                }

                $operations[] = $method.' /'.substr($route->uri(), 7);
            }
        }

        sort($operations);

        return array_values(array_unique($operations));
    }

    #[Test]
    public function la_specification_est_un_document_openapi_valide(): void
    {
        $spec = DocumentationController::specificationArray();

        $this->assertStringStartsWith('3.', (string) $spec['openapi']);
        $this->assertNotEmpty($spec['info']['title']);
        $this->assertArrayHasKey('bearerAuth', $spec['components']['securitySchemes']);
    }

    #[Test]
    public function chaque_route_exposee_est_documentee(): void
    {
        $missing = array_diff($this->registeredOperations(), $this->documentedOperations());

        $this->assertSame([], array_values($missing), 'Routes non documentées dans openapi.yaml.');
    }

    #[Test]
    public function chaque_operation_documentee_existe(): void
    {
        $stale = array_diff($this->documentedOperations(), $this->registeredOperations());

        $this->assertSame([], array_values($stale), 'Opérations documentées sans route correspondante.');
    }

    /** @param  list<string>  $values */
    #[Test]
    #[DataProvider('enums')]
    public function les_enums_documentes_refletent_les_enums_php(string $schema, array $values): void
    {
        $spec = DocumentationController::specificationArray();

        $this->assertSame($values, $spec['components']['schemas'][$schema]['enum'], "Schéma {$schema} désynchronisé.");
    }

    /** @return iterable<string, array{string, list<string>}> */
    public static function enums(): iterable
    {
        yield 'WorkspaceRole' => ['WorkspaceRole', WorkspaceRole::values()];
        yield 'AssignableWorkspaceRole' => ['AssignableWorkspaceRole', WorkspaceRole::assignableValues()];
        yield 'KnowledgeSourceType' => ['KnowledgeSourceType', KnowledgeSourceType::values()];
        yield 'KnowledgeDocumentType' => ['KnowledgeDocumentType', KnowledgeDocumentType::values()];
        yield 'KnowledgeDocumentStatus' => ['KnowledgeDocumentStatus', KnowledgeDocumentStatus::values()];
        yield 'RecrawlFrequency' => ['RecrawlFrequency', RecrawlFrequency::values()];
        yield 'ConversationStatus' => ['ConversationStatus', ConversationStatus::values()];
        yield 'ConversationChannel' => ['ConversationChannel', ConversationChannel::values()];
        yield 'MessageSenderType' => ['MessageSenderType', MessageSenderType::values()];
        yield 'MessageVisibility' => ['MessageVisibility', MessageVisibility::values()];
        yield 'WidgetPosition' => ['WidgetPosition', WidgetPosition::values()];
        yield 'BillingInterval' => ['BillingInterval', BillingInterval::values()];
        yield 'SubscriptionStatus' => ['SubscriptionStatus', SubscriptionStatus::values()];
        yield 'InvoiceStatus' => ['InvoiceStatus', InvoiceStatus::values()];
    }

    #[Test]
    public function la_documentation_est_servie(): void
    {
        $this->get('/docs')->assertOk()->assertSee('swagger-ui', false);
        $this->getJson('/docs/openapi.json')->assertOk()->assertJsonPath('info.title', DocumentationController::specificationArray()['info']['title']);
    }
}
