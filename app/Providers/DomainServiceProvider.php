<?php

namespace App\Providers;

use App\Domains\Assistant\Contracts\AiProviderContract;
use App\Domains\Assistant\Contracts\AssistantResponderContract;
use App\Domains\Assistant\Contracts\AssistantSettingsRepositoryContract;
use App\Domains\Assistant\Contracts\ConversationInsightContract;
use App\Domains\Assistant\Providers\ArrayAiProvider;
use App\Domains\Assistant\Providers\ClaudeAiProvider;
use App\Domains\Assistant\Repositories\EloquentAssistantSettingsRepository;
use App\Domains\Assistant\Services\AssistantService;
use App\Domains\Auth\Contracts\AccessTokenManagerContract;
use App\Domains\Auth\Contracts\PasswordResetTokenStoreContract;
use App\Domains\Auth\Contracts\TotpProviderContract;
use App\Domains\Auth\Services\SanctumAccessTokenManager;
use App\Domains\Auth\Support\BrokerPasswordResetTokenStore;
use App\Domains\Auth\Support\EmailVerificationToken;
use App\Domains\Auth\Support\Google2faTotpProvider;
use App\Domains\Billing\Contracts\AiCreditLedgerContract;
use App\Domains\Billing\Contracts\InvoiceRepositoryContract;
use App\Domains\Billing\Contracts\PlanRepositoryContract;
use App\Domains\Billing\Contracts\QuotaGuardContract;
use App\Domains\Billing\Contracts\SubscriptionProvisionerContract;
use App\Domains\Billing\Contracts\SubscriptionRepositoryContract;
use App\Domains\Billing\Contracts\SubscriptionWebhookContract;
use App\Domains\Billing\Repositories\EloquentAiCreditLedger;
use App\Domains\Billing\Repositories\EloquentInvoiceRepository;
use App\Domains\Billing\Repositories\EloquentPlanRepository;
use App\Domains\Billing\Repositories\EloquentSubscriptionRepository;
use App\Domains\Billing\Services\QuotaGuardService;
use App\Domains\Billing\Services\SubscriptionService;
use App\Domains\Conversations\Contracts\CannedResponseRepositoryContract;
use App\Domains\Conversations\Contracts\ConversationReaderContract;
use App\Domains\Conversations\Contracts\ConversationRepositoryContract;
use App\Domains\Conversations\Contracts\MessageRepositoryContract;
use App\Domains\Conversations\Contracts\VisitorConversationContract;
use App\Domains\Conversations\Repositories\EloquentCannedResponseRepository;
use App\Domains\Conversations\Repositories\EloquentConversationRepository;
use App\Domains\Conversations\Repositories\EloquentMessageRepository;
use App\Domains\Conversations\Services\ConversationService;
use App\Domains\Knowledge\Contracts\EmbeddingProviderContract;
use App\Domains\Knowledge\Contracts\EmbeddingSearchContract;
use App\Domains\Knowledge\Contracts\KnowledgeChunkRepositoryContract;
use App\Domains\Knowledge\Contracts\KnowledgeDocumentRepositoryContract;
use App\Domains\Knowledge\Contracts\KnowledgeSearchContract;
use App\Domains\Knowledge\Contracts\KnowledgeSourceRepositoryContract;
use App\Domains\Knowledge\Contracts\OcrEngineContract;
use App\Domains\Knowledge\Contracts\WebCrawlerContract;
use App\Domains\Knowledge\Crawling\ArrayWebCrawler;
use App\Domains\Knowledge\Crawling\SimpleWebCrawler;
use App\Domains\Knowledge\Embeddings\ArrayEmbeddingProvider;
use App\Domains\Knowledge\Embeddings\VoyageAiEmbeddingProvider;
use App\Domains\Knowledge\Extractors\DocxTextExtractor;
use App\Domains\Knowledge\Extractors\PdfTextExtractor;
use App\Domains\Knowledge\Extractors\PlainTextExtractor;
use App\Domains\Knowledge\Extractors\SpreadsheetTextExtractor;
use App\Domains\Knowledge\Ocr\ArrayOcrEngine;
use App\Domains\Knowledge\Ocr\ClaudeOcrEngine;
use App\Domains\Knowledge\Repositories\EloquentKnowledgeChunkRepository;
use App\Domains\Knowledge\Repositories\EloquentKnowledgeDocumentRepository;
use App\Domains\Knowledge\Repositories\EloquentKnowledgeSourceRepository;
use App\Domains\Knowledge\Search\MySqlCosineSimilaritySearch;
use App\Domains\Knowledge\Services\KnowledgeSearchService;
use App\Domains\Knowledge\Support\KnowledgeFileStorage;
use App\Domains\Knowledge\Support\TextExtractorRegistry;
use App\Domains\Notifications\Contracts\MailSenderContract;
use App\Domains\Notifications\Contracts\TransactionalMailerContract;
use App\Domains\Notifications\Senders\ArrayMailSender;
use App\Domains\Notifications\Senders\LaravelMailSender;
use App\Domains\Notifications\Services\TransactionalMailer;
use App\Domains\Notifications\Support\FrontendUrl;
use App\Domains\Payments\Contracts\PaymentGatewayContract;
use App\Domains\Payments\Gateways\ArrayPaymentGateway;
use App\Domains\Payments\Gateways\StripePaymentGateway;
use App\Domains\Payments\Services\CheckoutService;
use App\Domains\Shared\Contracts\TransactionManagerContract;
use App\Domains\Shared\Support\DatabaseTransactionManager;
use App\Domains\Users\Contracts\UserRepositoryContract;
use App\Domains\Users\Repositories\EloquentUserRepository;
use App\Domains\Widget\Contracts\WidgetSettingsRepositoryContract;
use App\Domains\Widget\Repositories\EloquentWidgetSettingsRepository;
use App\Domains\Widget\Services\WidgetSettingsService;
use App\Domains\Widget\Support\VisitorSessionToken;
use App\Domains\Workspaces\Contracts\InvitationRepositoryContract;
use App\Domains\Workspaces\Contracts\MembershipReaderContract;
use App\Domains\Workspaces\Contracts\MembershipRepositoryContract;
use App\Domains\Workspaces\Contracts\PermissionMatrixContract;
use App\Domains\Workspaces\Contracts\WorkspacePlatformReaderContract;
use App\Domains\Workspaces\Contracts\WorkspaceProvisionerContract;
use App\Domains\Workspaces\Contracts\WorkspaceRepositoryContract;
use App\Domains\Workspaces\Repositories\EloquentInvitationRepository;
use App\Domains\Workspaces\Repositories\EloquentMembershipRepository;
use App\Domains\Workspaces\Repositories\EloquentWorkspaceRepository;
use App\Domains\Workspaces\Services\InvitationService;
use App\Domains\Workspaces\Services\WorkspaceService;
use App\Domains\Workspaces\Support\SpatiePermissionMatrix;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\ServiceProvider;
use PragmaRX\Google2FA\Google2FA;
use RuntimeException;

/**
 * Point unique de câblage entre contrats et implémentations (inversion des
 * dépendances).
 *
 * Aucun service métier ne référence une classe concrète de persistance ni de
 * prestataire : substituer une implémentation, en test ou en production, ne
 * touche que ce fichier.
 */
class DomainServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        // --- Socle -------------------------------------------------------------
        TransactionManagerContract::class => DatabaseTransactionManager::class,

        // --- Comptes et sessions -------------------------------------------------
        UserRepositoryContract::class => EloquentUserRepository::class,
        AccessTokenManagerContract::class => SanctumAccessTokenManager::class,

        // --- Espaces de travail --------------------------------------------------
        WorkspaceRepositoryContract::class => EloquentWorkspaceRepository::class,
        MembershipRepositoryContract::class => EloquentMembershipRepository::class,
        InvitationRepositoryContract::class => EloquentInvitationRepository::class,
        PermissionMatrixContract::class => SpatiePermissionMatrix::class,

        // --- Lectures étroites (ségrégation des interfaces) ----------------------
        //
        // `MembershipReaderContract` : Auth sait dans quels espaces un compte
        // agit, sans pouvoir modifier une équipe.
        // `WorkspaceProvisionerContract` : l'inscription ouvre un espace, sans
        // accéder au reste de leur gestion.
        MembershipReaderContract::class => EloquentMembershipRepository::class,
        WorkspaceProvisionerContract::class => WorkspaceService::class,

        // --- Notifications ---------------------------------------------------------
        TransactionalMailerContract::class => TransactionalMailer::class,

        // --- Connaissances (lot 1) --------------------------------------------------
        KnowledgeSourceRepositoryContract::class => EloquentKnowledgeSourceRepository::class,
        KnowledgeDocumentRepositoryContract::class => EloquentKnowledgeDocumentRepository::class,
        KnowledgeChunkRepositoryContract::class => EloquentKnowledgeChunkRepository::class,
        // Moteur de recherche vectorielle : MySQL + similarité cosinus en PHP, pas de
        // pgvector (décision du 8 octobre 2026, section 12 du cahier des charges).
        EmbeddingSearchContract::class => MySqlCosineSimilaritySearch::class,
        // Lecture étroite exposée au domaine `Assistant` (lot 2) : il cherche,
        // il n'indexe pas.
        KnowledgeSearchContract::class => KnowledgeSearchService::class,

        // --- Conversations (lot 2) --------------------------------------------------
        ConversationRepositoryContract::class => EloquentConversationRepository::class,
        MessageRepositoryContract::class => EloquentMessageRepository::class,
        CannedResponseRepositoryContract::class => EloquentCannedResponseRepository::class,
        // Lecture étroite exposée au domaine `Widget` : un visiteur écrit et note,
        // sans rien connaître de l'affectation ni des notes internes.
        VisitorConversationContract::class => ConversationService::class,

        // --- Widget (lot 2) ----------------------------------------------------------
        WidgetSettingsRepositoryContract::class => EloquentWidgetSettingsRepository::class,

        // --- Agent IA (lot 2) ---------------------------------------------------------
        AssistantSettingsRepositoryContract::class => EloquentAssistantSettingsRepository::class,
        // Lecture étroite exposée au domaine `Conversations` : il déclenche une
        // réponse et lit résumé/sentiment, sans rien connaître du fournisseur d'IA.
        AssistantResponderContract::class => AssistantService::class,
        ConversationInsightContract::class => AssistantService::class,

        // --- Facturation (lot 3) ------------------------------------------------------
        PlanRepositoryContract::class => EloquentPlanRepository::class,
        SubscriptionRepositoryContract::class => EloquentSubscriptionRepository::class,
        InvoiceRepositoryContract::class => EloquentInvoiceRepository::class,
        AiCreditLedgerContract::class => EloquentAiCreditLedger::class,
        // Lecture étroite exposée au domaine `Workspaces` : ouvrir un espace ouvre
        // aussi son essai gratuit, sans connaître les plans.
        SubscriptionProvisionerContract::class => SubscriptionService::class,
        // « Puis-je ? » : les domaines demandent sans connaître les plans.
        QuotaGuardContract::class => QuotaGuardService::class,
        // Lecture étroite exposée au domaine `Payments` : un évènement du
        // prestataire met à jour un abonnement, sans connaître les plans ni les
        // crédits IA.
        SubscriptionWebhookContract::class => SubscriptionService::class,

        // --- Lectures étroites exposées à la console plateforme (lot 3) --------------
        WorkspacePlatformReaderContract::class => EloquentWorkspaceRepository::class,

        // --- Conversation : lecture étroite exposée au domaine `Analytics` (lot 3) ---
        // Analytics lit, n'écrit pas.
        ConversationReaderContract::class => EloquentConversationRepository::class,
    ];

    public function register(): void
    {
        $this->registerMailSender();
        $this->registerAuthSupport();
        $this->registerKnowledgeProviders();
        $this->registerAssistantProvider();
        $this->registerWidgetSupport();
        $this->registerPaymentsProvider();

        $this->app->bind(FrontendUrl::class, fn (): FrontendUrl => new FrontendUrl(
            (string) config('voxo.frontend_url'),
        ));

        $this->app->when(InvitationService::class)
            ->needs('$ttlHours')
            ->giveConfig('voxo.invitations.ttl_hours');

        $this->app->when(CheckoutService::class)
            ->needs('$frontendUrl')
            ->giveConfig('voxo.frontend_url');
    }

    /**
     * Pilote d'envoi des e-mails, choisi par configuration.
     *
     * `ArrayMailSender` est enregistré comme singleton sous son propre nom, et
     * pas seulement derrière le contrat : les tests le redemandent par sa
     * classe concrète (`tests\TestCase::mailbox()`) pour inspecter les envois,
     * et doivent obtenir exactement l'instance que les services ont reçue, pas
     * une seconde en double.
     *
     * Un pilote inconnu lève au lieu de retomber sur un autre : une plateforme
     * qui croit envoyer ses liens de vérification alors qu'elle les jette ne
     * se découvrirait qu'au premier client bloqué.
     */
    private function registerMailSender(): void
    {
        $this->app->singleton(ArrayMailSender::class);
        $this->app->singleton(LaravelMailSender::class);

        $this->app->singleton(MailSenderContract::class, function (): MailSenderContract {
            $name = (string) config('notifications.mail.driver');
            $driver = config("notifications.mail.drivers.{$name}");

            if (! is_string($driver) || ! is_subclass_of($driver, MailSenderContract::class)) {
                throw new RuntimeException(
                    "Pilote d'e-mail « {$name} » inconnu. Vérifiez VOXO_MAIL_DRIVER et config/notifications.php.",
                );
            }

            return $this->app->make($driver);
        });
    }

    /**
     * Fournisseurs externes du domaine `Knowledge`, choisis par configuration.
     *
     * Les doublures de test (`Array*`) sont enregistrées comme singletons sous
     * leur propre nom, pas seulement derrière leur contrat : les tests les
     * redemandent par leur classe concrète pour préparer des réponses ou
     * inspecter les appels reçus, et doivent obtenir l'instance exacte que les
     * services ont reçue.
     *
     * Un pilote inconnu lève au lieu de retomber sur un autre : voir
     * `registerMailSender()` pour la même garde.
     */
    private function registerKnowledgeProviders(): void
    {
        $this->app->singleton(ArrayEmbeddingProvider::class);
        $this->app->singleton(ArrayOcrEngine::class);
        $this->app->singleton(ArrayWebCrawler::class);

        $this->app->singleton(EmbeddingProviderContract::class, function (): EmbeddingProviderContract {
            $name = (string) config('knowledge.embeddings.driver');
            $driver = config("knowledge.embeddings.drivers.{$name}");

            if (! is_string($driver) || ! is_subclass_of($driver, EmbeddingProviderContract::class)) {
                throw new RuntimeException(
                    "Pilote d'embeddings « {$name} » inconnu. Vérifiez KNOWLEDGE_EMBEDDINGS_DRIVER et config/knowledge.php.",
                );
            }

            return $driver === VoyageAiEmbeddingProvider::class
                ? new VoyageAiEmbeddingProvider(
                    (string) config('knowledge.embeddings.voyage.key'),
                    (string) config('knowledge.embeddings.voyage.model'),
                    (string) config('knowledge.embeddings.voyage.base_url'),
                )
                : $this->app->make($driver);
        });

        $this->app->singleton(OcrEngineContract::class, function (): OcrEngineContract {
            $name = (string) config('knowledge.ocr.driver');
            $driver = config("knowledge.ocr.drivers.{$name}");

            if (! is_string($driver) || ! is_subclass_of($driver, OcrEngineContract::class)) {
                throw new RuntimeException(
                    "Pilote d'OCR « {$name} » inconnu. Vérifiez KNOWLEDGE_OCR_DRIVER et config/knowledge.php.",
                );
            }

            return $driver === ClaudeOcrEngine::class
                ? new ClaudeOcrEngine(
                    (string) config('knowledge.ocr.claude.key'),
                    (string) config('knowledge.ocr.claude.model'),
                    (string) config('knowledge.ocr.claude.base_url'),
                )
                : $this->app->make($driver);
        });

        $this->app->singleton(WebCrawlerContract::class, function (): WebCrawlerContract {
            $name = (string) config('knowledge.crawler.driver');
            $driver = config("knowledge.crawler.drivers.{$name}");

            if (! is_string($driver) || ! is_subclass_of($driver, WebCrawlerContract::class)) {
                throw new RuntimeException(
                    "Pilote d'exploration « {$name} » inconnu. Vérifiez KNOWLEDGE_CRAWLER_DRIVER et config/knowledge.php.",
                );
            }

            return $driver === SimpleWebCrawler::class
                ? new SimpleWebCrawler((int) config('knowledge.crawler.max_pages_per_crawl'))
                : $this->app->make($driver);
        });

        $this->app->singleton(TextExtractorRegistry::class, fn (): TextExtractorRegistry => new TextExtractorRegistry([
            new PlainTextExtractor,
            new PdfTextExtractor,
            new DocxTextExtractor,
            new SpreadsheetTextExtractor,
        ]));

        $this->app->bind(KnowledgeFileStorage::class, fn (): KnowledgeFileStorage => new KnowledgeFileStorage(
            (string) config('knowledge.storage_disk'),
        ));
    }

    /** Fournisseur d'IA de l'agent, choisi par configuration (même garde que `registerMailSender()`). */
    private function registerAssistantProvider(): void
    {
        $this->app->singleton(ArrayAiProvider::class);

        $this->app->singleton(AiProviderContract::class, function (): AiProviderContract {
            $name = (string) config('assistant.driver');
            $driver = config("assistant.drivers.{$name}");

            if (! is_string($driver) || ! is_subclass_of($driver, AiProviderContract::class)) {
                throw new RuntimeException(
                    "Pilote d'IA « {$name} » inconnu. Vérifiez ASSISTANT_AI_DRIVER et config/assistant.php.",
                );
            }

            return $driver === ClaudeAiProvider::class
                ? new ClaudeAiProvider(
                    (string) config('assistant.claude.key'),
                    (string) config('assistant.claude.respond_model'),
                    (string) config('assistant.claude.short_task_model'),
                    (string) config('assistant.claude.base_url'),
                )
                : $this->app->make($driver);
        });
    }

    /** Prestataire de paiement, choisi par configuration (même garde que `registerMailSender()`). */
    private function registerPaymentsProvider(): void
    {
        $this->app->singleton(ArrayPaymentGateway::class);

        $this->app->singleton(PaymentGatewayContract::class, function (): PaymentGatewayContract {
            $name = (string) config('payments.driver');
            $driver = config("payments.drivers.{$name}");

            if (! is_string($driver) || ! is_subclass_of($driver, PaymentGatewayContract::class)) {
                throw new RuntimeException(
                    "Pilote de paiement « {$name} » inconnu. Vérifiez PAYMENTS_GATEWAY_DRIVER et config/payments.php.",
                );
            }

            return $driver === StripePaymentGateway::class
                ? new StripePaymentGateway(
                    (string) config('payments.stripe.key'),
                    (string) config('payments.stripe.webhook_secret'),
                )
                : $this->app->make($driver);
        });
    }

    private function registerWidgetSupport(): void
    {
        $this->app->bind(VisitorSessionToken::class, fn (): VisitorSessionToken => new VisitorSessionToken(
            (string) config('app.key'),
            (int) config('widget.visitor_session.ttl_minutes'),
        ));

        $this->app->when(WidgetSettingsService::class)
            ->needs('$scriptBaseUrl')
            ->giveConfig('widget.script_base_url');
    }

    private function registerAuthSupport(): void
    {
        $this->app->bind(EmailVerificationToken::class, fn (): EmailVerificationToken => new EmailVerificationToken(
            (string) config('app.key'),
            (int) config('voxo.email_verification.ttl_minutes'),
        ));

        $this->app->bind(TotpProviderContract::class, fn (): TotpProviderContract => new Google2faTotpProvider(
            new Google2FA,
            (string) config('voxo.two_factor.issuer'),
        ));

        $this->app->bind(PasswordResetTokenStoreContract::class, function (): PasswordResetTokenStoreContract {
            /** @var PasswordBroker $broker */
            $broker = Password::broker();

            return new BrokerPasswordResetTokenStore($broker);
        });
    }
}
