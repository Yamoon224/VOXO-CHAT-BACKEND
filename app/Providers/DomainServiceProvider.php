<?php

namespace App\Providers;

use App\Domains\Assistant\Contracts\AgentToolRepositoryContract;
use App\Domains\Assistant\Contracts\AiProviderContract;
use App\Domains\Assistant\Contracts\AssistantResponderContract;
use App\Domains\Assistant\Contracts\AssistantSettingsRepositoryContract;
use App\Domains\Assistant\Contracts\AvailableToolsContract;
use App\Domains\Assistant\Contracts\ConversationInsightContract;
use App\Domains\Assistant\Contracts\ToolCallerContract;
use App\Domains\Assistant\Contracts\ToolInvokerContract;
use App\Domains\Assistant\Providers\ArrayAgentToolCaller;
use App\Domains\Assistant\Providers\ArrayAiProvider;
use App\Domains\Assistant\Providers\ClaudeAiProvider;
use App\Domains\Assistant\Repositories\EloquentAgentToolRepository;
use App\Domains\Assistant\Repositories\EloquentAssistantSettingsRepository;
use App\Domains\Assistant\Services\AgentToolService;
use App\Domains\Assistant\Services\AssistantService;
use App\Domains\Auth\Contracts\AccessTokenManagerContract;
use App\Domains\Auth\Contracts\ApiKeyIssuerContract;
use App\Domains\Auth\Contracts\PasswordResetTokenStoreContract;
use App\Domains\Auth\Contracts\TotpProviderContract;
use App\Domains\Auth\Services\SanctumAccessTokenManager;
use App\Domains\Auth\Support\BrokerPasswordResetTokenStore;
use App\Domains\Auth\Support\EmailVerificationToken;
use App\Domains\Auth\Support\Google2faTotpProvider;
use App\Domains\Automations\Contracts\AutomationResponderContract;
use App\Domains\Automations\Contracts\AutomationRunRepositoryContract;
use App\Domains\Automations\Contracts\AutomationScenarioRepositoryContract;
use App\Domains\Automations\Repositories\EloquentAutomationRunRepository;
use App\Domains\Automations\Repositories\EloquentAutomationScenarioRepository;
use App\Domains\Automations\Services\AutomationExecutionService;
use App\Domains\Billing\Contracts\AiCreditLedgerContract;
use App\Domains\Billing\Contracts\InvoiceRepositoryContract;
use App\Domains\Billing\Contracts\PlanRepositoryContract;
use App\Domains\Billing\Contracts\QuotaAlertRepositoryContract;
use App\Domains\Billing\Contracts\QuotaGuardContract;
use App\Domains\Billing\Contracts\SubscriptionProvisionerContract;
use App\Domains\Billing\Contracts\SubscriptionRepositoryContract;
use App\Domains\Billing\Contracts\SubscriptionWebhookContract;
use App\Domains\Billing\Repositories\EloquentAiCreditLedger;
use App\Domains\Billing\Repositories\EloquentInvoiceRepository;
use App\Domains\Billing\Repositories\EloquentPlanRepository;
use App\Domains\Billing\Repositories\EloquentQuotaAlertRepository;
use App\Domains\Billing\Repositories\EloquentSubscriptionRepository;
use App\Domains\Billing\Services\QuotaGuardService;
use App\Domains\Billing\Services\SubscriptionService;
use App\Domains\Campaigns\Contracts\CampaignRepositoryContract;
use App\Domains\Campaigns\Contracts\CampaignSendRepositoryContract;
use App\Domains\Campaigns\Contracts\CampaignTemplateRepositoryContract;
use App\Domains\Campaigns\Repositories\EloquentCampaignRepository;
use App\Domains\Campaigns\Repositories\EloquentCampaignSendRepository;
use App\Domains\Campaigns\Repositories\EloquentCampaignTemplateRepository;
use App\Domains\Channels\Contracts\ChannelConnectionRepositoryContract;
use App\Domains\Channels\Contracts\ChannelEmailSettingsRepositoryContract;
use App\Domains\Channels\Contracts\EmailChannelProviderContract;
use App\Domains\Channels\Contracts\InstagramChannelProviderContract;
use App\Domains\Channels\Contracts\LineChannelProviderContract;
use App\Domains\Channels\Contracts\MessengerChannelProviderContract;
use App\Domains\Channels\Contracts\OutboundChannelDispatcherContract;
use App\Domains\Channels\Contracts\SmsChannelProviderContract;
use App\Domains\Channels\Contracts\TelegramChannelProviderContract;
use App\Domains\Channels\Contracts\TelephonyChannelProviderContract;
use App\Domains\Channels\Contracts\ViberChannelProviderContract;
use App\Domains\Channels\Contracts\WhatsAppChannelProviderContract;
use App\Domains\Channels\Contracts\XChannelProviderContract;
use App\Domains\Channels\Providers\ArrayEmailChannelProvider;
use App\Domains\Channels\Providers\ArrayInstagramChannelProvider;
use App\Domains\Channels\Providers\ArrayLineChannelProvider;
use App\Domains\Channels\Providers\ArrayMessengerChannelProvider;
use App\Domains\Channels\Providers\ArraySmsChannelProvider;
use App\Domains\Channels\Providers\ArrayTelegramChannelProvider;
use App\Domains\Channels\Providers\ArrayTelephonyChannelProvider;
use App\Domains\Channels\Providers\ArrayViberChannelProvider;
use App\Domains\Channels\Providers\ArrayWhatsAppChannelProvider;
use App\Domains\Channels\Providers\ArrayXChannelProvider;
use App\Domains\Channels\Repositories\EloquentChannelConnectionRepository;
use App\Domains\Channels\Repositories\EloquentChannelEmailSettingsRepository;
use App\Domains\Channels\Services\ChannelDispatchService;
use App\Domains\Contacts\Contracts\ContactEventRepositoryContract;
use App\Domains\Contacts\Contracts\ContactLinkContract;
use App\Domains\Contacts\Contracts\ContactNoteRepositoryContract;
use App\Domains\Contacts\Contracts\ContactRepositoryContract;
use App\Domains\Contacts\Contracts\ContactSegmentMatcherContract;
use App\Domains\Contacts\Contracts\ContactSegmentRepositoryContract;
use App\Domains\Contacts\Repositories\EloquentContactEventRepository;
use App\Domains\Contacts\Repositories\EloquentContactNoteRepository;
use App\Domains\Contacts\Repositories\EloquentContactRepository;
use App\Domains\Contacts\Repositories\EloquentContactSegmentRepository;
use App\Domains\Contacts\Services\ContactSegmentService;
use App\Domains\Contacts\Services\ContactService;
use App\Domains\Conversations\Contracts\CannedResponseRepositoryContract;
use App\Domains\Conversations\Contracts\ConversationReaderContract;
use App\Domains\Conversations\Contracts\ConversationRepositoryContract;
use App\Domains\Conversations\Contracts\InboxViewRepositoryContract;
use App\Domains\Conversations\Contracts\MessageRepositoryContract;
use App\Domains\Conversations\Contracts\RoutingRuleRepositoryContract;
use App\Domains\Conversations\Contracts\VisitorConversationContract;
use App\Domains\Conversations\Repositories\EloquentCannedResponseRepository;
use App\Domains\Conversations\Repositories\EloquentConversationRepository;
use App\Domains\Conversations\Repositories\EloquentInboxViewRepository;
use App\Domains\Conversations\Repositories\EloquentMessageRepository;
use App\Domains\Conversations\Repositories\EloquentRoutingRuleRepository;
use App\Domains\Conversations\Services\ConversationService;
use App\Domains\HelpCenter\Contracts\HelpCenterArticleRepositoryContract;
use App\Domains\HelpCenter\Contracts\HelpCenterCategoryRepositoryContract;
use App\Domains\HelpCenter\Repositories\EloquentHelpCenterArticleRepository;
use App\Domains\HelpCenter\Repositories\EloquentHelpCenterCategoryRepository;
use App\Domains\Integrations\Contracts\WebhookDeliveryRepositoryContract;
use App\Domains\Integrations\Contracts\WebhookDispatcherContract;
use App\Domains\Integrations\Contracts\WebhookSubscriptionRepositoryContract;
use App\Domains\Integrations\Repositories\EloquentWebhookDeliveryRepository;
use App\Domains\Integrations\Repositories\EloquentWebhookSubscriptionRepository;
use App\Domains\Integrations\Services\WebhookDispatchService;
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
use App\Domains\Payments\Contracts\PaymentGatewayContract;
use App\Domains\Payments\Gateways\ArrayPaymentGateway;
use App\Domains\Payments\Gateways\StripePaymentGateway;
use App\Domains\Payments\Services\CheckoutService;
use App\Domains\Realtime\Contracts\ConversationBroadcasterContract;
use App\Domains\Realtime\Providers\ArrayConversationBroadcaster;
use App\Domains\Shared\Contracts\TransactionManagerContract;
use App\Domains\Shared\Support\DatabaseTransactionManager;
use App\Domains\Shared\Support\FrontendUrl;
use App\Domains\StatusPage\Contracts\ProbeCheckerContract;
use App\Domains\StatusPage\Contracts\StatusIncidentRepositoryContract;
use App\Domains\StatusPage\Contracts\StatusIncidentUpdateRepositoryContract;
use App\Domains\StatusPage\Contracts\StatusMaintenanceRepositoryContract;
use App\Domains\StatusPage\Contracts\StatusProbeCheckRepositoryContract;
use App\Domains\StatusPage\Contracts\StatusProbeRepositoryContract;
use App\Domains\StatusPage\Contracts\StatusSubscriberRepositoryContract;
use App\Domains\StatusPage\Providers\ArrayProbeChecker;
use App\Domains\StatusPage\Repositories\EloquentStatusIncidentRepository;
use App\Domains\StatusPage\Repositories\EloquentStatusIncidentUpdateRepository;
use App\Domains\StatusPage\Repositories\EloquentStatusMaintenanceRepository;
use App\Domains\StatusPage\Repositories\EloquentStatusProbeCheckRepository;
use App\Domains\StatusPage\Repositories\EloquentStatusProbeRepository;
use App\Domains\StatusPage\Repositories\EloquentStatusSubscriberRepository;
use App\Domains\StatusPage\Support\StatusSubscriberToken;
use App\Domains\Tickets\Contracts\TicketRepositoryContract;
use App\Domains\Tickets\Repositories\EloquentTicketRepository;
use App\Domains\Tickets\Support\TicketPortalToken;
use App\Domains\Users\Contracts\UserRepositoryContract;
use App\Domains\Users\Repositories\EloquentUserRepository;
use App\Domains\Widget\Contracts\WidgetSettingsRepositoryContract;
use App\Domains\Widget\Repositories\EloquentWidgetSettingsRepository;
use App\Domains\Widget\Services\WidgetSettingsService;
use App\Domains\Widget\Support\VisitorPrincipal;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // Lecture étroite exposée au domaine `Integrations` (lot 6) : une clé
        // d'API n'est qu'un jeton d'accès personnel marqué comme tel.
        ApiKeyIssuerContract::class => SanctumAccessTokenManager::class,

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

        // --- Contacts (lot 4) ---------------------------------------------------------
        ContactRepositoryContract::class => EloquentContactRepository::class,
        ContactNoteRepositoryContract::class => EloquentContactNoteRepository::class,
        ContactEventRepositoryContract::class => EloquentContactEventRepository::class,
        ContactSegmentRepositoryContract::class => EloquentContactSegmentRepository::class,
        // Lecture étroite exposée aux domaines qui démarrent une conversation
        // (`Widget`, `Channels`) : retrouver ou créer un contact par e-mail.
        ContactLinkContract::class => ContactService::class,
        // Lecture étroite exposée au domaine `Campaigns` (lot 6, section 2.9) :
        // résoudre l'appartenance à un segment, jamais gérer les segments.
        ContactSegmentMatcherContract::class => ContactSegmentService::class,

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

        // --- Outils tiers déclarés (lot 7, section 2.2, P3) ---------------------------
        AgentToolRepositoryContract::class => EloquentAgentToolRepository::class,
        // Lecture étroite exposée à `AssistantService` : quels outils proposer.
        AvailableToolsContract::class => AgentToolService::class,
        // Lecture étroite exposée à `ClaudeAiProvider` : comment invoquer un outil.
        ToolInvokerContract::class => AgentToolService::class,

        // --- Facturation (lot 3) ------------------------------------------------------
        PlanRepositoryContract::class => EloquentPlanRepository::class,
        SubscriptionRepositoryContract::class => EloquentSubscriptionRepository::class,
        InvoiceRepositoryContract::class => EloquentInvoiceRepository::class,
        AiCreditLedgerContract::class => EloquentAiCreditLedger::class,
        QuotaAlertRepositoryContract::class => EloquentQuotaAlertRepository::class,
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

        // --- Routage et sous-boîtes (lot 5) ---------------------------------------------
        RoutingRuleRepositoryContract::class => EloquentRoutingRuleRepository::class,
        InboxViewRepositoryContract::class => EloquentInboxViewRepository::class,

        // --- Automatisations (lot 5, section 2.8) ---------------------------------------
        AutomationScenarioRepositoryContract::class => EloquentAutomationScenarioRepository::class,
        AutomationRunRepositoryContract::class => EloquentAutomationRunRepository::class,
        // Lecture étroite exposée au domaine `Conversations` : un message
        // vient d'être reçu, à évaluer contre les scénarios publiés.
        AutomationResponderContract::class => AutomationExecutionService::class,

        // --- Canaux (lot 4, lot 5) -------------------------------------------------------
        ChannelEmailSettingsRepositoryContract::class => EloquentChannelEmailSettingsRepository::class,
        ChannelConnectionRepositoryContract::class => EloquentChannelConnectionRepository::class,
        // Lecture étroite exposée au domaine `Conversations` : une réponse
        // publique vient d'être postée, à relayer vers son canal d'origine.
        OutboundChannelDispatcherContract::class => ChannelDispatchService::class,

        // --- Tickets (lot 4) ------------------------------------------------------------
        TicketRepositoryContract::class => EloquentTicketRepository::class,

        // --- Centre d'aide (lot 4) ------------------------------------------------------
        HelpCenterCategoryRepositoryContract::class => EloquentHelpCenterCategoryRepository::class,
        HelpCenterArticleRepositoryContract::class => EloquentHelpCenterArticleRepository::class,

        // --- Page de statut (lot 7, section 2.11) ----------------------------------------
        StatusProbeRepositoryContract::class => EloquentStatusProbeRepository::class,
        StatusProbeCheckRepositoryContract::class => EloquentStatusProbeCheckRepository::class,
        StatusIncidentRepositoryContract::class => EloquentStatusIncidentRepository::class,
        StatusIncidentUpdateRepositoryContract::class => EloquentStatusIncidentUpdateRepository::class,
        StatusMaintenanceRepositoryContract::class => EloquentStatusMaintenanceRepository::class,
        StatusSubscriberRepositoryContract::class => EloquentStatusSubscriberRepository::class,

        // --- Campagnes (lot 6, section 2.9) : envois ponctuels, modèles ------------------
        CampaignTemplateRepositoryContract::class => EloquentCampaignTemplateRepository::class,
        CampaignRepositoryContract::class => EloquentCampaignRepository::class,
        CampaignSendRepositoryContract::class => EloquentCampaignSendRepository::class,

        // --- Intégrations (lot 6, section 2.14) : clés d'API, webhooks sortants ---------
        WebhookSubscriptionRepositoryContract::class => EloquentWebhookSubscriptionRepository::class,
        WebhookDeliveryRepositoryContract::class => EloquentWebhookDeliveryRepository::class,
        // Lecture étroite exposée aux domaines métier : un évènement vient de
        // se produire, à relayer vers les abonnements qui y souscrivent.
        WebhookDispatcherContract::class => WebhookDispatchService::class,
    ];

    /**
     * Garde `visitor` (section 4.1, authentification des canaux temps réel
     * publics) : reconstruit une identité anonyme à partir du jeton de
     * session à chaque requête d'authentification de canal, jamais stockée.
     * Essayé seulement après le garde `sanctum` (voir `routes/channels.php`),
     * donc jamais consulté pour une requête d'agent authentifié.
     */
    public function boot(): void
    {
        Auth::viaRequest('visitor', function (Request $request): ?VisitorPrincipal {
            // Les bibliothèques clientes (Echo) ne savent envoyer que des
            // en-têtes supplémentaires sur `/broadcasting/auth`, pas de champ
            // de formulaire arbitraire : `X-Visitor-Token` sert ce besoin, le
            // champ `visitor_token` reste accepté pour un appel direct.
            $token = $request->header('X-Visitor-Token') ?? $request->input('visitor_token');

            if (! is_string($token) || $token === '') {
                return null;
            }

            $claims = $this->app->make(VisitorSessionToken::class)->verify($token, now());

            return $claims === null ? null : new VisitorPrincipal($claims['workspaceId'], $claims['visitorId']);
        });
    }

    public function register(): void
    {
        $this->registerMailSender();
        $this->registerAuthSupport();
        $this->registerKnowledgeProviders();
        $this->registerToolCallerProvider();
        $this->registerAssistantProvider();
        $this->registerWidgetSupport();
        $this->registerPaymentsProvider();
        $this->registerChannelsProvider();
        $this->registerTicketsSupport();
        $this->registerStatusPageProvider();
        $this->registerRealtimeProvider();

        $this->app->bind(StatusSubscriberToken::class, fn (): StatusSubscriberToken => new StatusSubscriberToken(
            (string) config('app.key'),
        ));

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
                    $this->app->make(ToolInvokerContract::class),
                )
                : $this->app->make($driver);
        });
    }

    /** Pilote d'appel HTTP des outils tiers déclarés, choisi par configuration (même garde que `registerMailSender()`). */
    private function registerToolCallerProvider(): void
    {
        $this->app->singleton(ArrayAgentToolCaller::class);

        $this->app->singleton(ToolCallerContract::class, function (): ToolCallerContract {
            $name = (string) config('assistant.tool_caller.driver');
            $driver = config("assistant.tool_caller.drivers.{$name}");

            if (! is_string($driver) || ! is_subclass_of($driver, ToolCallerContract::class)) {
                throw new RuntimeException(
                    "Pilote d'outils « {$name} » inconnu. Vérifiez ASSISTANT_TOOL_CALLER_DRIVER et config/assistant.php.",
                );
            }

            return $this->app->make($driver);
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

    /** Pilote d'envoi des canaux de messagerie, choisi par configuration (même garde que `registerMailSender()`). */
    private function registerChannelsProvider(): void
    {
        $this->app->singleton(ArrayEmailChannelProvider::class);
        $this->app->singleton(ArrayWhatsAppChannelProvider::class);
        $this->app->singleton(ArrayMessengerChannelProvider::class);
        $this->app->singleton(ArrayInstagramChannelProvider::class);
        $this->app->singleton(ArrayTelegramChannelProvider::class);
        $this->app->singleton(ArraySmsChannelProvider::class);
        $this->app->singleton(ArrayXChannelProvider::class);
        $this->app->singleton(ArrayViberChannelProvider::class);
        $this->app->singleton(ArrayLineChannelProvider::class);
        $this->app->singleton(ArrayTelephonyChannelProvider::class);

        $this->bindChannelDriver('email', EmailChannelProviderContract::class);
        $this->bindChannelDriver('whatsapp', WhatsAppChannelProviderContract::class);
        $this->bindChannelDriver('messenger', MessengerChannelProviderContract::class);
        $this->bindChannelDriver('instagram', InstagramChannelProviderContract::class);
        $this->bindChannelDriver('telegram', TelegramChannelProviderContract::class);
        $this->bindChannelDriver('sms', SmsChannelProviderContract::class);
        $this->bindChannelDriver('x', XChannelProviderContract::class);
        $this->bindChannelDriver('viber', ViberChannelProviderContract::class);
        $this->bindChannelDriver('line', LineChannelProviderContract::class);
        $this->bindChannelDriver('telephony', TelephonyChannelProviderContract::class);
    }

    /**
     * @template T
     *
     * @param  class-string<T>  $contract
     */
    private function bindChannelDriver(string $channel, string $contract): void
    {
        $this->app->singleton($contract, function () use ($channel, $contract) {
            $name = (string) config("channels.{$channel}.driver");
            $driver = config("channels.{$channel}.drivers.{$name}");

            if (! is_string($driver) || ! is_subclass_of($driver, $contract)) {
                throw new RuntimeException(
                    "Pilote du canal « {$channel} » « {$name} » inconnu. Vérifiez CHANNELS_".strtoupper($channel).'_DRIVER et config/channels.php.',
                );
            }

            return $this->app->make($driver);
        });
    }

    private function registerTicketsSupport(): void
    {
        $this->app->bind(TicketPortalToken::class, fn (): TicketPortalToken => new TicketPortalToken(
            (string) config('app.key'),
            (int) config('tickets.portal.ttl_days'),
        ));
    }

    /** Pilote de vérification des sondes, choisi par configuration (même garde que `registerMailSender()`). */
    private function registerStatusPageProvider(): void
    {
        $this->app->singleton(ArrayProbeChecker::class);

        $this->app->singleton(ProbeCheckerContract::class, function (): ProbeCheckerContract {
            $name = (string) config('status_page.probe_checker.driver');
            $driver = config("status_page.probe_checker.drivers.{$name}");

            if (! is_string($driver) || ! is_subclass_of($driver, ProbeCheckerContract::class)) {
                throw new RuntimeException(
                    "Pilote de vérification « {$name} » inconnu. Vérifiez STATUS_PAGE_PROBE_CHECKER_DRIVER et config/status_page.php.",
                );
            }

            return $this->app->make($driver);
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

    /** Pilote de diffusion temps réel (section 4.1), choisi par configuration (même garde que `registerToolCallerProvider()`). */
    private function registerRealtimeProvider(): void
    {
        $this->app->singleton(ArrayConversationBroadcaster::class);

        $this->app->singleton(ConversationBroadcasterContract::class, function (): ConversationBroadcasterContract {
            $name = (string) config('realtime.broadcaster.driver');
            $driver = config("realtime.broadcaster.drivers.{$name}");

            if (! is_string($driver) || ! is_subclass_of($driver, ConversationBroadcasterContract::class)) {
                throw new RuntimeException(
                    "Pilote de diffusion « {$name} » inconnu. Vérifiez REALTIME_BROADCASTER_DRIVER et config/realtime.php.",
                );
            }

            return $this->app->make($driver);
        });
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
