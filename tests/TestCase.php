<?php

namespace Tests;

use App\Domains\Assistant\Providers\ArrayAiProvider;
use App\Domains\Auth\Contracts\AccessTokenManagerContract;
use App\Domains\Knowledge\Crawling\ArrayWebCrawler;
use App\Domains\Knowledge\Embeddings\ArrayEmbeddingProvider;
use App\Domains\Knowledge\Ocr\ArrayOcrEngine;
use App\Domains\Notifications\Senders\ArrayMailSender;
use App\Domains\Payments\Gateways\ArrayPaymentGateway;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Les rôles et la grille tarifaire viennent des seeders versionnés et non
     * de matrices inventées pour les tests : un test qui s'appuierait sur des
     * permissions ou des paliers fabriqués à la main passerait avec des
     * données de production différentes. Toute inscription provisionne un
     * essai (`SubscriptionProvisionerContract`), qui exige un palier actif.
     */
    protected bool $seed = true;

    /** @var class-string */
    protected string $seeder = DatabaseSeeder::class;

    /**
     * Ajoute un compte à un espace de travail avec le rôle donné, et renvoie
     * ce compte — pour s'authentifier dans cet espace avec `actingInWorkspace`.
     *
     * Pour obtenir l'adhésion elle-même (son identifiant, distinct de celui
     * du compte), voir `addMember`.
     */
    protected function memberOf(Workspace $workspace, WorkspaceRole $role, ?User $user = null): User
    {
        return $this->addMember($workspace, $role, $user)->user;
    }

    /** Ajoute un compte à un espace de travail et renvoie l'adhésion elle-même, compte chargé. */
    protected function addMember(Workspace $workspace, WorkspaceRole $role, ?User $user = null): WorkspaceMember
    {
        $user ??= User::factory()->create();

        $member = WorkspaceMember::factory()->role($role)->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
        ]);

        return $member->setRelation('user', $user);
    }

    /**
     * Authentifie les requêtes suivantes par un vrai jeton porteur de l'espace
     * de travail, exactement comme le fera un client : le périmètre se déduit
     * du jeton, un utilisateur simulé sans jeton ne prouverait donc rien.
     */
    protected function actingInWorkspace(User $user, ?Workspace $workspace): static
    {
        // Le garde conserve le compte résolu d'une requête à l'autre au sein
        // d'un même test : sans cet oubli, changer de jeton ne changerait pas
        // d'appelant.
        $this->app->make('auth')->forgetGuards();

        return $this->withToken(
            $this->app->make(AccessTokenManagerContract::class)->issue($user, $workspace?->id, 'test'),
        );
    }

    /** Les requêtes suivantes partent sans jeton. */
    protected function asGuest(): static
    {
        $this->app->make('auth')->forgetGuards();

        return $this->withoutToken();
    }

    /**
     * L'instance exacte utilisée par l'application le temps du test
     * (`VOXO_MAIL_DRIVER=array` en environnement de test) : voir
     * `DomainServiceProvider::registerMailSender()`.
     */
    protected function mailbox(): ArrayMailSender
    {
        return $this->app->make(ArrayMailSender::class);
    }

    /**
     * L'instance exacte utilisée par l'application le temps du test
     * (`KNOWLEDGE_CRAWLER_DRIVER=array` en environnement de test).
     */
    protected function webCrawler(): ArrayWebCrawler
    {
        return $this->app->make(ArrayWebCrawler::class);
    }

    /** `KNOWLEDGE_OCR_DRIVER=array` en environnement de test. */
    protected function ocrEngine(): ArrayOcrEngine
    {
        return $this->app->make(ArrayOcrEngine::class);
    }

    /** `KNOWLEDGE_EMBEDDINGS_DRIVER=array` en environnement de test. */
    protected function embeddingProvider(): ArrayEmbeddingProvider
    {
        return $this->app->make(ArrayEmbeddingProvider::class);
    }

    /** `ASSISTANT_AI_DRIVER=array` en environnement de test. */
    protected function aiProvider(): ArrayAiProvider
    {
        return $this->app->make(ArrayAiProvider::class);
    }

    /** `PAYMENTS_GATEWAY_DRIVER=array` en environnement de test. */
    protected function paymentGateway(): ArrayPaymentGateway
    {
        return $this->app->make(ArrayPaymentGateway::class);
    }
}
