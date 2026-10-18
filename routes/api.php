<?php

use App\Domains\Analytics\Http\Controllers\AnalyticsController;
use App\Domains\Analytics\Http\Controllers\AnalyticsExportController;
use App\Domains\Assistant\Http\Controllers\AssistantSandboxController;
use App\Domains\Assistant\Http\Controllers\AssistantSettingsController;
use App\Domains\Auth\Http\Controllers\AuthController;
use App\Domains\Auth\Http\Controllers\EmailVerificationController;
use App\Domains\Auth\Http\Controllers\PasswordResetController;
use App\Domains\Auth\Http\Controllers\TwoFactorController;
use App\Domains\Automations\Http\Controllers\AutomationScenarioController;
use App\Domains\Billing\Http\Controllers\InvoiceController;
use App\Domains\Billing\Http\Controllers\PlanController;
use App\Domains\Billing\Http\Controllers\SubscriptionController;
use App\Domains\Campaigns\Http\Controllers\CampaignController;
use App\Domains\Campaigns\Http\Controllers\CampaignTemplateController;
use App\Domains\Channels\Http\Controllers\ChannelConnectionController;
use App\Domains\Channels\Http\Controllers\ChannelEmailSettingsController;
use App\Domains\Channels\Http\Controllers\ContactFormController;
use App\Domains\Channels\Http\Controllers\InboundEmailController;
use App\Domains\Channels\Http\Controllers\InstagramInboundController;
use App\Domains\Channels\Http\Controllers\MessengerInboundController;
use App\Domains\Channels\Http\Controllers\SmsInboundController;
use App\Domains\Channels\Http\Controllers\TelegramInboundController;
use App\Domains\Channels\Http\Controllers\WhatsAppInboundController;
use App\Domains\Channels\Support\MessagingChannel;
use App\Domains\Contacts\Http\Controllers\ContactController;
use App\Domains\Contacts\Http\Controllers\ContactEventController;
use App\Domains\Contacts\Http\Controllers\ContactExportController;
use App\Domains\Contacts\Http\Controllers\ContactImportController;
use App\Domains\Contacts\Http\Controllers\ContactNoteController;
use App\Domains\Contacts\Http\Controllers\ContactSegmentController;
use App\Domains\Conversations\Http\Controllers\CannedResponseController;
use App\Domains\Conversations\Http\Controllers\ConversationController;
use App\Domains\Conversations\Http\Controllers\InboxViewController;
use App\Domains\Conversations\Http\Controllers\MessageController;
use App\Domains\Conversations\Http\Controllers\RoutingRuleController;
use App\Domains\HelpCenter\Http\Controllers\HelpCenterArticleController;
use App\Domains\HelpCenter\Http\Controllers\HelpCenterCategoryController;
use App\Domains\HelpCenter\Http\Controllers\PublicHelpCenterController;
use App\Domains\Integrations\Http\Controllers\ApiKeyController;
use App\Domains\Integrations\Http\Controllers\WebhookSubscriptionController;
use App\Domains\Knowledge\Http\Controllers\KnowledgeDocumentController;
use App\Domains\Knowledge\Http\Controllers\KnowledgeQaEntryController;
use App\Domains\Knowledge\Http\Controllers\KnowledgeSearchController;
use App\Domains\Knowledge\Http\Controllers\KnowledgeSourceController;
use App\Domains\Knowledge\Http\Controllers\KnowledgeUploadController;
use App\Domains\Knowledge\Http\Controllers\KnowledgeWebsiteSourceController;
use App\Domains\Payments\Http\Controllers\CheckoutController;
use App\Domains\Payments\Http\Controllers\WebhookController;
use App\Domains\Platform\Http\Controllers\PlatformWorkspaceController;
use App\Domains\Shared\Http\Controllers\HealthController;
use App\Domains\Tickets\Http\Controllers\OpenTicketFromConversationController;
use App\Domains\Tickets\Http\Controllers\PublicTicketController;
use App\Domains\Tickets\Http\Controllers\TicketController;
use App\Domains\Tickets\Http\Controllers\TicketPortalController;
use App\Domains\Tickets\Http\Controllers\TicketPortalLinkController;
use App\Domains\Tickets\Http\Controllers\TicketPortalMessageController;
use App\Domains\Users\Http\Controllers\ProfileController;
use App\Domains\Widget\Http\Controllers\PublicWidgetSettingsController;
use App\Domains\Widget\Http\Controllers\VisitorMessageController;
use App\Domains\Widget\Http\Controllers\VisitorRatingController;
use App\Domains\Widget\Http\Controllers\VisitorSessionController;
use App\Domains\Widget\Http\Controllers\WidgetSettingsController;
use App\Domains\Workspaces\Http\Controllers\InvitationAcceptanceController;
use App\Domains\Workspaces\Http\Controllers\InvitationController;
use App\Domains\Workspaces\Http\Controllers\MemberController;
use App\Domains\Workspaces\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST — préfixe /api/v1
|--------------------------------------------------------------------------
|
| Conventions : ressources au pluriel, verbes HTTP porteurs de l'intention,
| 201 sur création, 204 sans corps, 422 sur validation, 409 sur conflit
| d'état métier.
|
| Trois zones :
|
|   1. Publique : inscription, connexion, liens reçus par e-mail. Limitée en
|      débit.
|   2. Compte : tout ce qui concerne l'appelant lui-même, quel que soit
|      l'espace de travail (`auth:sanctum`).
|   3. Espace de travail : le périmètre vient du jeton (`workspace`), jamais
|      de l'URL ni du corps. C'est pourquoi la ressource s'écrit `/workspace`
|      au singulier : il n'y a pas d'identifiant à fournir, donc pas
|      d'identifiant à falsifier.
|
*/

Route::get('/health', HealthController::class);

// =============================================================================
// Zone publique
// =============================================================================

Route::middleware('throttle:10,1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/email/verify', [EmailVerificationController::class, 'verify']);
    Route::post('/auth/password/forgot', [PasswordResetController::class, 'forgot']);
    Route::post('/auth/password/reset', [PasswordResetController::class, 'reset']);
});

Route::middleware('throttle:30,1')->group(function (): void {
    Route::get('/invitations/{token}', [InvitationAcceptanceController::class, 'show']);
    Route::post('/invitations/{token}/accept', [InvitationAcceptanceController::class, 'accept']);
});

// Widget public : un visiteur n'est pas un compte, le périmètre vient du
// jeton de session de visiteur (signé, sans table de sessions), pas d'un
// jeton Sanctum. Pas de bundle `widget.js` embarquable pour l'instant (voir
// la note de portée du lot 2) : cette zone n'est consommée qu'en REST, par
// sondage — le temps réel (Reverb, section 4.1) suivra avec le widget lui-même.
Route::prefix('public/widget')->middleware('throttle:60,1')->group(function (): void {
    Route::get('/{workspace}/settings', [PublicWidgetSettingsController::class, 'show'])->whereUuid('workspace');
    Route::post('/{workspace}/sessions', [VisitorSessionController::class, 'store'])->whereUuid('workspace');
    Route::post('/messages', [VisitorMessageController::class, 'store']);
    Route::get('/messages', [VisitorMessageController::class, 'index']);
    Route::post('/ratings', [VisitorRatingController::class, 'store']);
});

// Canaux entrants publics (lot 4, section 2.5) : un visiteur ou un
// prestataire d'e-mail entrant (non encore choisi, voir `EmailChannelProviderContract`)
// y rattache un e-mail à un espace de travail, par son identifiant dans
// l'URL — même raisonnement que `public/widget` : pas de compte, pas de
// jeton Sanctum.
Route::prefix('public/channels')->middleware('throttle:60,1')->group(function (): void {
    Route::post('/email/{workspace}/inbound', [InboundEmailController::class, 'store'])->whereUuid('workspace');
    Route::post('/contact-form/{workspace}/submissions', [ContactFormController::class, 'store'])->whereUuid('workspace');

    // Omnicanal (lot 5) : WhatsApp, Messenger et Instagram passent par le
    // webhook unifié de Meta, qui exige une poignée de main GET avant
    // d'accepter l'URL (`VerifiesMetaWebhook`) — Telegram et le SMS appellent
    // directement le POST, sans vérification préalable.
    Route::get('/whatsapp/{workspace}/inbound', [WhatsAppInboundController::class, 'verify'])->whereUuid('workspace');
    Route::post('/whatsapp/{workspace}/inbound', [WhatsAppInboundController::class, 'store'])->whereUuid('workspace');
    Route::get('/messenger/{workspace}/inbound', [MessengerInboundController::class, 'verify'])->whereUuid('workspace');
    Route::post('/messenger/{workspace}/inbound', [MessengerInboundController::class, 'store'])->whereUuid('workspace');
    Route::get('/instagram/{workspace}/inbound', [InstagramInboundController::class, 'verify'])->whereUuid('workspace');
    Route::post('/instagram/{workspace}/inbound', [InstagramInboundController::class, 'store'])->whereUuid('workspace');
    Route::post('/telegram/{workspace}/inbound', [TelegramInboundController::class, 'store'])->whereUuid('workspace');
    Route::post('/sms/{workspace}/inbound', [SmsInboundController::class, 'store'])->whereUuid('workspace');
});

// Portail client des tickets (lot 4, section 2.7) : pas de compte, un lien
// signé reçu par e-mail à l'ouverture donne accès à l'historique du contact
// (voir `TicketPortalToken`) — même raisonnement que `public/widget`, mais un
// jeton qui vit des mois plutôt que des minutes. Les routes `portal/...` sont
// déclarées avant celles à `{workspace}` : littérales, elles n'ont pas besoin
// de l'arbitrage de `whereUuid` pour ne jamais s'y confondre.
Route::prefix('public/tickets')->middleware('throttle:60,1')->group(function (): void {
    Route::get('/portal/tickets', [TicketPortalController::class, 'index']);
    Route::get('/portal/tickets/{ticket}', [TicketPortalController::class, 'show'])->whereUuid('ticket');
    Route::post('/portal/tickets/{ticket}/messages', [TicketPortalMessageController::class, 'store'])->whereUuid('ticket');
    Route::post('/{workspace}/submissions', [PublicTicketController::class, 'store'])->whereUuid('workspace');
    Route::post('/{workspace}/portal-links', [TicketPortalLinkController::class, 'store'])->whereUuid('workspace');
});

// Pages publiques du centre d'aide (lot 4, section 2.10) : pas de compte, pas
// de jeton — seul le brouillon reste caché (`HelpCenterPublicService`).
Route::prefix('public/help-center')->middleware('throttle:60,1')->group(function (): void {
    Route::get('/{workspace}/categories', [PublicHelpCenterController::class, 'categories'])->whereUuid('workspace');
    Route::get('/{workspace}/articles', [PublicHelpCenterController::class, 'articles'])->whereUuid('workspace');
    Route::get('/{workspace}/articles/{slug}', [PublicHelpCenterController::class, 'article'])->whereUuid('workspace');
});

// Rappels du prestataire de paiement (lot 3) : l'authenticité tient à la
// signature du corps, vérifiée dans `PaymentGatewayContract::parseWebhookEvent()`
// — jamais à un jeton Sanctum, que Stripe ne détient pas.
Route::post('/public/payments/webhook', [WebhookController::class, 'store']);

// Grille tarifaire publique (page « Tarifs » du site vitrine, lot 3/4) :
// mêmes données que `/billing/plans`, sans exiger de compte.
Route::middleware('throttle:60,1')->get('/public/plans', [PlanController::class, 'index']);

// =============================================================================
// Zone compte
// =============================================================================

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1');

    Route::post('/auth/two-factor', [TwoFactorController::class, 'enable']);
    Route::post('/auth/two-factor/confirm', [TwoFactorController::class, 'confirm']);
    Route::delete('/auth/two-factor', [TwoFactorController::class, 'disable']);

    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::post('/workspaces', [WorkspaceController::class, 'store']);
    Route::post('/workspaces/{workspace}/switch', [WorkspaceController::class, 'switch'])->whereUuid('workspace');

    // =========================================================================
    // Zone espace de travail
    // =========================================================================

    Route::middleware('workspace')->prefix('workspace')->group(function (): void {
        Route::get('/', [WorkspaceController::class, 'show'])->middleware('workspace.can:workspace.view');
        Route::put('/', [WorkspaceController::class, 'update'])->middleware('workspace.can:workspace.manage');

        Route::get('/members', [MemberController::class, 'index'])->middleware('workspace.can:members.view');

        Route::middleware('workspace.can:members.manage')->group(function (): void {
            Route::put('/members/{member}', [MemberController::class, 'update'])->whereUuid('member');
            Route::delete('/members/{member}', [MemberController::class, 'destroy'])->whereUuid('member');

            Route::get('/invitations', [InvitationController::class, 'index']);
            Route::post('/invitations', [InvitationController::class, 'store']);
            Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->whereUuid('invitation');
        });

        // =====================================================================
        // Base de connaissances (lot 1)
        // =====================================================================

        Route::middleware('workspace.can:knowledge.view')->group(function (): void {
            Route::get('/knowledge/sources', [KnowledgeSourceController::class, 'index']);
            Route::get('/knowledge/documents', [KnowledgeDocumentController::class, 'index']);
            Route::get('/knowledge/documents/{document}', [KnowledgeDocumentController::class, 'show'])->whereUuid('document');
            Route::get('/knowledge/qa-entries', [KnowledgeQaEntryController::class, 'index']);
            Route::post('/knowledge/search', [KnowledgeSearchController::class, 'store']);
        });

        Route::middleware('workspace.can:knowledge.manage')->group(function (): void {
            Route::post('/knowledge/uploads', [KnowledgeUploadController::class, 'store']);
            Route::post('/knowledge/websites', [KnowledgeWebsiteSourceController::class, 'store']);
            Route::delete('/knowledge/sources/{source}', [KnowledgeSourceController::class, 'destroy'])->whereUuid('source');
            Route::post('/knowledge/sources/{source}/recrawl', [KnowledgeSourceController::class, 'recrawl'])->whereUuid('source');
            Route::delete('/knowledge/documents/{document}', [KnowledgeDocumentController::class, 'destroy'])->whereUuid('document');
            Route::post('/knowledge/documents/{document}/retry', [KnowledgeDocumentController::class, 'retry'])->whereUuid('document');
            Route::post('/knowledge/qa-entries', [KnowledgeQaEntryController::class, 'store']);
            Route::put('/knowledge/qa-entries/{document}', [KnowledgeQaEntryController::class, 'update'])->whereUuid('document');
            Route::delete('/knowledge/qa-entries/{document}', [KnowledgeQaEntryController::class, 'destroy'])->whereUuid('document');
        });

        // =====================================================================
        // Conversation : boîte de réception, widget, agent IA (lot 2)
        // =====================================================================

        Route::middleware('workspace.can:conversations.view')->group(function (): void {
            Route::get('/conversations', [ConversationController::class, 'index']);
            Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->whereUuid('conversation');
            Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index'])->whereUuid('conversation');
            Route::get('/inbox-views', [InboxViewController::class, 'index']);
        });

        Route::middleware('workspace.can:conversations.manage')->group(function (): void {
            Route::put('/conversations/{conversation}/status', [ConversationController::class, 'updateStatus'])->whereUuid('conversation');
            Route::put('/conversations/{conversation}/assignment', [ConversationController::class, 'updateAssignment'])->whereUuid('conversation');
            Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->whereUuid('conversation');
            Route::post('/conversations/{conversation}/summarize', [ConversationController::class, 'summarize'])->whereUuid('conversation');
            Route::post('/conversations/{conversation}/sentiment', [ConversationController::class, 'analyzeSentiment'])->whereUuid('conversation');
            Route::post('/conversations/{conversation}/suggest-reply', [ConversationController::class, 'suggestReply'])->whereUuid('conversation');
            Route::post('/inbox-views', [InboxViewController::class, 'store']);
            Route::put('/inbox-views/{view}', [InboxViewController::class, 'update'])->whereUuid('view');
            Route::delete('/inbox-views/{view}', [InboxViewController::class, 'destroy'])->whereUuid('view');
        });

        // =====================================================================
        // Routage (lot 5, section 2.4) : tourniquet, origine, intention.
        // =====================================================================

        Route::middleware('workspace.can:routing.manage')->group(function (): void {
            Route::get('/routing-rules', [RoutingRuleController::class, 'index']);
            Route::post('/routing-rules', [RoutingRuleController::class, 'store']);
            Route::put('/routing-rules/{rule}', [RoutingRuleController::class, 'update'])->whereUuid('rule');
            Route::delete('/routing-rules/{rule}', [RoutingRuleController::class, 'destroy'])->whereUuid('rule');
        });

        // =====================================================================
        // Automatisations (lot 5, section 2.8) : déclencheur, conditions,
        // actions — brouillon et test avant publication.
        // =====================================================================

        Route::middleware('workspace.can:automations.manage')->group(function (): void {
            Route::get('/automations', [AutomationScenarioController::class, 'index']);
            Route::post('/automations', [AutomationScenarioController::class, 'store']);
            Route::put('/automations/{scenario}', [AutomationScenarioController::class, 'update'])->whereUuid('scenario');
            Route::delete('/automations/{scenario}', [AutomationScenarioController::class, 'destroy'])->whereUuid('scenario');
            Route::post('/automations/{scenario}/test', [AutomationScenarioController::class, 'test'])->whereUuid('scenario');
        });

        // =====================================================================
        // Campagnes (lot 6, section 2.9) : envois ponctuels, ciblage par
        // segment, modèles.
        // =====================================================================

        Route::middleware('workspace.can:campaigns.manage')->group(function (): void {
            Route::get('/campaign-templates', [CampaignTemplateController::class, 'index']);
            Route::post('/campaign-templates', [CampaignTemplateController::class, 'store']);
            Route::put('/campaign-templates/{template}', [CampaignTemplateController::class, 'update'])->whereUuid('template');
            Route::delete('/campaign-templates/{template}', [CampaignTemplateController::class, 'destroy'])->whereUuid('template');

            Route::get('/campaigns', [CampaignController::class, 'index']);
            Route::post('/campaigns', [CampaignController::class, 'store']);
            Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->whereUuid('campaign');
            Route::put('/campaigns/{campaign}', [CampaignController::class, 'update'])->whereUuid('campaign');
            Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->whereUuid('campaign');
            Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send'])->whereUuid('campaign');
            Route::get('/campaigns/{campaign}/sends', [CampaignController::class, 'sends'])->whereUuid('campaign');
        });

        // =====================================================================
        // Tickets (lot 4) : une conversation devient ticket, priorité, échéance.
        // =====================================================================

        Route::middleware('workspace.can:tickets.view')->group(function (): void {
            Route::get('/tickets', [TicketController::class, 'index']);
            Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->whereUuid('ticket');
        });

        Route::middleware('workspace.can:tickets.manage')->group(function (): void {
            Route::post('/conversations/{conversation}/ticket', [OpenTicketFromConversationController::class, 'store'])->whereUuid('conversation');
            Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->whereUuid('ticket');
        });

        // =====================================================================
        // Centre d'aide (lot 4) : catégories et articles publics, section 2.10.
        // =====================================================================

        Route::middleware('workspace.can:help_center.view')->group(function (): void {
            Route::get('/help-center/categories', [HelpCenterCategoryController::class, 'index']);
            Route::get('/help-center/articles', [HelpCenterArticleController::class, 'index']);
            Route::get('/help-center/articles/{article}', [HelpCenterArticleController::class, 'show'])->whereUuid('article');
        });

        Route::middleware('workspace.can:help_center.manage')->group(function (): void {
            Route::post('/help-center/categories', [HelpCenterCategoryController::class, 'store']);
            Route::put('/help-center/categories/{category}', [HelpCenterCategoryController::class, 'update'])->whereUuid('category');
            Route::delete('/help-center/categories/{category}', [HelpCenterCategoryController::class, 'destroy'])->whereUuid('category');
            Route::post('/help-center/articles', [HelpCenterArticleController::class, 'store']);
            Route::put('/help-center/articles/{article}', [HelpCenterArticleController::class, 'update'])->whereUuid('article');
            Route::delete('/help-center/articles/{article}', [HelpCenterArticleController::class, 'destroy'])->whereUuid('article');
        });

        Route::middleware('workspace.can:canned_responses.manage')->group(function (): void {
            Route::get('/canned-responses', [CannedResponseController::class, 'index']);
            Route::post('/canned-responses', [CannedResponseController::class, 'store']);
            Route::put('/canned-responses/{cannedResponse}', [CannedResponseController::class, 'update'])->whereUuid('cannedResponse');
            Route::delete('/canned-responses/{cannedResponse}', [CannedResponseController::class, 'destroy'])->whereUuid('cannedResponse');
        });

        Route::middleware('workspace.can:widget.manage')->group(function (): void {
            Route::get('/widget/settings', [WidgetSettingsController::class, 'show']);
            Route::put('/widget/settings', [WidgetSettingsController::class, 'update']);
            Route::get('/widget/script', [WidgetSettingsController::class, 'script']);
        });

        // =====================================================================
        // Canaux (lot 4) : e-mail (boîte partagée, domaine d'envoi personnalisé).
        // Le formulaire de contact n'a pas de réglages, rien à exposer ici.
        // =====================================================================

        Route::middleware('workspace.can:channels.manage')->group(function (): void {
            Route::get('/channels/email/settings', [ChannelEmailSettingsController::class, 'show']);
            Route::put('/channels/email/settings', [ChannelEmailSettingsController::class, 'update']);

            // Omnicanal (lot 5) : une connexion par couple (espace, canal) —
            // WhatsApp, Messenger, Instagram, Telegram, SMS.
            Route::get('/channels/{channel}/connection', [ChannelConnectionController::class, 'show'])
                ->whereIn('channel', MessagingChannel::values());
            Route::put('/channels/{channel}/connection', [ChannelConnectionController::class, 'update'])
                ->whereIn('channel', MessagingChannel::values());
        });

        // =====================================================================
        // CRM (lot 4) : profils, notes, historique, segments, import/export CSV.
        // =====================================================================

        Route::middleware('workspace.can:contacts.view')->group(function (): void {
            Route::get('/contacts', [ContactController::class, 'index']);
            Route::get('/contacts/export', [ContactExportController::class, 'index']);
            Route::get('/contacts/{contact}', [ContactController::class, 'show'])->whereUuid('contact');
            Route::get('/contacts/{contact}/notes', [ContactNoteController::class, 'index'])->whereUuid('contact');
            Route::get('/contacts/{contact}/events', [ContactEventController::class, 'index'])->whereUuid('contact');
            Route::get('/contact-segments', [ContactSegmentController::class, 'index']);
            Route::get('/contact-segments/{segment}/contacts', [ContactSegmentController::class, 'contacts'])->whereUuid('segment');
        });

        Route::middleware('workspace.can:contacts.manage')->group(function (): void {
            Route::post('/contacts', [ContactController::class, 'store']);
            Route::post('/contacts/import', [ContactImportController::class, 'store']);
            Route::put('/contacts/{contact}', [ContactController::class, 'update'])->whereUuid('contact');
            Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->whereUuid('contact');
            Route::post('/contacts/{contact}/notes', [ContactNoteController::class, 'store'])->whereUuid('contact');
            Route::post('/contact-segments', [ContactSegmentController::class, 'store']);
            Route::put('/contact-segments/{segment}', [ContactSegmentController::class, 'update'])->whereUuid('segment');
            Route::delete('/contact-segments/{segment}', [ContactSegmentController::class, 'destroy'])->whereUuid('segment');
        });

        Route::middleware('workspace.can:assistant.manage')->group(function (): void {
            Route::get('/assistant/settings', [AssistantSettingsController::class, 'show']);
            Route::put('/assistant/settings', [AssistantSettingsController::class, 'update']);
            Route::post('/assistant/sandbox', [AssistantSandboxController::class, 'store']);
        });

        // =====================================================================
        // Facturation et statistiques (lot 3)
        // =====================================================================

        Route::middleware('workspace.can:billing.view')->group(function (): void {
            Route::get('/billing/plans', [PlanController::class, 'index']);
            Route::get('/billing/subscription', [SubscriptionController::class, 'show']);
            Route::get('/billing/invoices', [InvoiceController::class, 'index']);
        });

        Route::middleware('workspace.can:billing.manage')->group(function (): void {
            Route::post('/billing/checkout', [CheckoutController::class, 'store']);
            Route::post('/billing/subscription/switch-to-free', [SubscriptionController::class, 'switchToFree']);
            Route::post('/billing/subscription/cancel', [SubscriptionController::class, 'cancel']);
        });

        Route::middleware('workspace.can:analytics.view')->group(function (): void {
            Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
            Route::get('/analytics/export', [AnalyticsExportController::class, 'index']);
        });

        // =====================================================================
        // Intégrations (lot 6, section 2.14) : clés d'API, webhooks sortants.
        // =====================================================================

        Route::middleware('workspace.can:integrations.manage')->group(function (): void {
            Route::get('/integrations/webhooks', [WebhookSubscriptionController::class, 'index']);
            Route::post('/integrations/webhooks', [WebhookSubscriptionController::class, 'store']);
            Route::put('/integrations/webhooks/{subscription}', [WebhookSubscriptionController::class, 'update'])->whereUuid('subscription');
            Route::delete('/integrations/webhooks/{subscription}', [WebhookSubscriptionController::class, 'destroy'])->whereUuid('subscription');
            Route::get('/integrations/webhooks/{subscription}/deliveries', [WebhookSubscriptionController::class, 'deliveries'])->whereUuid('subscription');

            Route::get('/integrations/api-keys', [ApiKeyController::class, 'index']);
            Route::post('/integrations/api-keys', [ApiKeyController::class, 'store']);
            Route::delete('/integrations/api-keys/{key}', [ApiKeyController::class, 'destroy'])->whereUuid('key');
        });
    });
});

// =============================================================================
// Console plateforme (lot 3) : lecture seule, réservée à `platform_admin`.
// =============================================================================

Route::middleware(['auth:sanctum', 'platform.admin'])->prefix('platform')->group(function (): void {
    Route::get('/workspaces', [PlatformWorkspaceController::class, 'index']);
    Route::get('/plans', [PlanController::class, 'adminIndex']);
    Route::post('/plans', [PlanController::class, 'store']);
    Route::put('/plans/{plan}', [PlanController::class, 'update'])->whereUuid('plan');
    Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->whereUuid('plan');
});
