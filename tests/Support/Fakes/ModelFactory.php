<?php

namespace Tests\Support\Fakes;

use App\Domains\Automations\Enums\AutomationRunStatus;
use App\Domains\Automations\Enums\AutomationStatus;
use App\Domains\Automations\Enums\AutomationTriggerType;
use App\Domains\Billing\Enums\BillingInterval;
use App\Domains\Billing\Enums\SubscriptionStatus;
use App\Domains\Conversations\Enums\ConversationChannel;
use App\Domains\Conversations\Enums\ConversationStatus;
use App\Domains\Conversations\Enums\MessageSenderType;
use App\Domains\Conversations\Enums\MessageVisibility;
use App\Domains\Conversations\Enums\RoutingActionType;
use App\Domains\HelpCenter\Enums\ArticleStatus;
use App\Domains\Knowledge\Enums\KnowledgeDocumentStatus;
use App\Domains\Knowledge\Enums\KnowledgeDocumentType;
use App\Domains\Knowledge\Enums\KnowledgeSourceType;
use App\Domains\Knowledge\Enums\RecrawlFrequency;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Domains\Tickets\Enums\TicketPriority;
use App\Domains\Widget\Enums\WidgetPosition;
use App\Models\AssistantSettings;
use App\Models\AutomationRun;
use App\Models\AutomationScenario;
use App\Models\CannedResponse;
use App\Models\ChannelConnection;
use App\Models\ChannelEmailSettings;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\HelpCenterArticle;
use App\Models\HelpCenterCategory;
use App\Models\InboxView;
use App\Models\Invoice;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeSource;
use App\Models\Message;
use App\Models\Plan;
use App\Models\RoutingRule;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WidgetSettings;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use DateTimeInterface;
use Illuminate\Support\Str;

/**
 * Fabrique des modèles en mémoire pour les tests unitaires : aucune base
 * n'est touchée, les attributs sont posés bruts.
 */
final class ModelFactory
{
    /** @param  array<string, mixed>  $attributes */
    public static function user(array $attributes = []): User
    {
        return (new User)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'name' => 'Awa Koné',
            'email' => 'awa@example.test',
            // Les doublures de hachage comparent en clair.
            'password' => 'password',
            'locale' => 'fr',
            'is_active' => true,
            'email_verified_at' => null,
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ], true);
    }

    public static function workspace(string $name = 'Acme', ?string $slug = null): Workspace
    {
        return (new Workspace)->setRawAttributes([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'slug' => $slug ?? Str::slug($name),
            'locale' => 'fr',
            'timezone' => 'UTC',
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function contact(array $attributes = []): Contact
    {
        if (array_key_exists('custom_attributes', $attributes) && is_array($attributes['custom_attributes'])) {
            $attributes['custom_attributes'] = json_encode($attributes['custom_attributes']);
        }

        return (new Contact)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'name' => 'Awa Koné',
            'email' => null,
            'phone' => null,
            'custom_attributes' => null,
        ], true);
    }

    public static function member(string $workspaceId, string $userId, WorkspaceRole $role): WorkspaceMember
    {
        return (new WorkspaceMember)->setRawAttributes([
            'id' => (string) Str::uuid(),
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'role' => $role->value,
        ], true);
    }

    public static function invitation(
        string $workspaceId,
        string $email,
        WorkspaceRole $role,
        string $tokenHash,
        DateTimeInterface $expiresAt,
    ): WorkspaceInvitation {
        return (new WorkspaceInvitation)->setRawAttributes([
            'id' => (string) Str::uuid(),
            'workspace_id' => $workspaceId,
            'email' => $email,
            'role' => $role->value,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'accepted_at' => null,
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function knowledgeSource(array $attributes = []): KnowledgeSource
    {
        return (new KnowledgeSource)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'type' => KnowledgeSourceType::Upload,
            'name' => 'Source de test',
            'website_url' => null,
            'website_sitemap_url' => null,
            'recrawl_frequency' => RecrawlFrequency::Manual,
            'last_crawled_at' => null,
            'created_by_user_id' => null,
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function knowledgeDocument(array $attributes = []): KnowledgeDocument
    {
        return (new KnowledgeDocument)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'source_id' => (string) Str::uuid(),
            'type' => KnowledgeDocumentType::File,
            'title' => 'Document de test',
            'origin_url' => null,
            'original_filename' => null,
            'mime_type' => null,
            'disk_path' => null,
            'raw_content' => null,
            'question' => null,
            'answer' => null,
            'status' => KnowledgeDocumentStatus::Pending,
            'status_message' => null,
            'chunk_count' => 0,
            'indexed_at' => null,
            'created_by_user_id' => null,
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function knowledgeChunk(array $attributes = []): KnowledgeChunk
    {
        return (new KnowledgeChunk)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'document_id' => (string) Str::uuid(),
            'position' => 0,
            'content' => 'Passage de test.',
            'token_count' => 10,
            'embedding' => null,
            'embedding_model' => null,
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function conversation(array $attributes = []): Conversation
    {
        return (new Conversation)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'channel' => ConversationChannel::Widget,
            'status' => ConversationStatus::Open,
            'visitor_id' => (string) Str::uuid(),
            'visitor_name' => null,
            'visitor_email' => null,
            'contact_id' => null,
            'assigned_user_id' => null,
            'subject' => null,
            'summary' => null,
            'sentiment' => null,
            'needs_human' => false,
            'rating' => null,
            'rating_comment' => null,
            'last_message_at' => null,
            'resolved_at' => null,
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function ticket(array $attributes = []): Ticket
    {
        return (new Ticket)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'conversation_id' => (string) Str::uuid(),
            'contact_id' => null,
            'priority' => TicketPriority::Normal,
            'due_at' => null,
            'created_at' => now(),
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function message(array $attributes = []): Message
    {
        return (new Message)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'conversation_id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'sender_type' => MessageSenderType::Visitor,
            'sender_user_id' => null,
            'visibility' => MessageVisibility::Public,
            'body' => 'Message de test.',
            'citations' => null,
            'attachment_path' => null,
            'attachment_filename' => null,
            'created_at' => now(),
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function helpCenterCategory(array $attributes = []): HelpCenterCategory
    {
        return (new HelpCenterCategory)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'name' => 'Catégorie de test',
            'slug' => 'categorie-de-test',
            'locale' => 'fr',
            'position' => 0,
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function helpCenterArticle(array $attributes = []): HelpCenterArticle
    {
        return (new HelpCenterArticle)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'category_id' => null,
            'title' => 'Article de test',
            'slug' => 'article-de-test',
            'locale' => 'fr',
            'body' => 'Contenu de test.',
            'status' => ArticleStatus::Draft,
            'meta_description' => null,
            'position' => 0,
            'published_at' => null,
            'created_at' => now(),
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function routingRule(array $attributes = []): RoutingRule
    {
        // `conditions`/`assignee_user_ids` sont des colonnes castées JSON :
        // `setRawAttributes` attend leur forme brute (encodée), jamais le
        // tableau PHP directement — voir la note de ModelFactory sur ce piège.
        foreach (['conditions', 'assignee_user_ids'] as $jsonField) {
            if (array_key_exists($jsonField, $attributes) && is_array($attributes[$jsonField])) {
                $attributes[$jsonField] = json_encode($attributes[$jsonField]);
            }
        }

        return (new RoutingRule)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'name' => 'Règle de test',
            'position' => 0,
            'is_active' => true,
            'conditions' => json_encode([]),
            'action_type' => RoutingActionType::AssignUser,
            'assignee_user_ids' => json_encode([]),
            'next_round_robin_index' => 0,
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function automationScenario(array $attributes = []): AutomationScenario
    {
        foreach (['conditions', 'steps'] as $jsonField) {
            if (array_key_exists($jsonField, $attributes) && is_array($attributes[$jsonField])) {
                $attributes[$jsonField] = json_encode($attributes[$jsonField]);
            }
        }

        return (new AutomationScenario)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'name' => 'Scénario de test',
            'position' => 0,
            'status' => AutomationStatus::Draft,
            'trigger_type' => AutomationTriggerType::ConversationOpened,
            'conditions' => json_encode([]),
            'steps' => json_encode([]),
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function automationRun(array $attributes = []): AutomationRun
    {
        return (new AutomationRun)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'conversation_id' => (string) Str::uuid(),
            'scenario_id' => (string) Str::uuid(),
            'current_step' => 0,
            'status' => AutomationRunStatus::Running,
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function inboxView(array $attributes = []): InboxView
    {
        if (array_key_exists('filters', $attributes) && is_array($attributes['filters'])) {
            $attributes['filters'] = json_encode($attributes['filters']);
        }

        return (new InboxView)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'name' => 'Vue de test',
            'position' => 0,
            'filters' => json_encode([]),
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function cannedResponse(array $attributes = []): CannedResponse
    {
        return (new CannedResponse)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'title' => 'Réponse de test',
            'body' => 'Corps de la réponse.',
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function channelEmailSettings(array $attributes = []): ChannelEmailSettings
    {
        return (new ChannelEmailSettings)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'enabled' => false,
            'reply_from_email' => null,
            'reply_from_name' => null,
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function channelConnection(array $attributes = []): ChannelConnection
    {
        if (array_key_exists('credentials', $attributes) && is_array($attributes['credentials'])) {
            $attributes['credentials'] = json_encode($attributes['credentials']);
        }

        return (new ChannelConnection)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'channel' => 'whatsapp',
            'enabled' => false,
            'credentials' => json_encode([]),
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function widgetSettings(array $attributes = []): WidgetSettings
    {
        return (new WidgetSettings)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'primary_color' => '#4F46E5',
            'logo_url' => null,
            'position' => WidgetPosition::BottomRight,
            'welcome_message' => null,
            'language' => 'fr',
            'business_hours' => null,
            'offline_message' => null,
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function assistantSettings(array $attributes = []): AssistantSettings
    {
        return (new AssistantSettings)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'enabled' => true,
            'tone_instructions' => null,
            'confidence_threshold' => 0.60,
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function plan(array $attributes = []): Plan
    {
        return (new Plan)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'slug' => 'starter',
            'name' => 'Starter',
            'price_cents' => 1900,
            'currency' => 'EUR',
            'billing_interval' => BillingInterval::Month,
            'max_seats' => 5,
            'max_contacts' => 2000,
            'ai_credits_per_month' => 1000,
            'max_knowledge_documents' => 200,
            'is_custom' => false,
            'is_active' => true,
            'sort_order' => 1,
            'provider_price_id' => null,
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function subscription(array $attributes = []): Subscription
    {
        return (new Subscription)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'plan_id' => (string) Str::uuid(),
            'status' => SubscriptionStatus::Trialing,
            'trial_ends_at' => now()->addDays(14),
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(14),
            'canceled_at' => null,
            'payment_provider' => null,
            'payment_provider_customer_id' => null,
            'payment_provider_subscription_id' => null,
        ], true);
    }

    /** @param  array<string, mixed>  $attributes */
    public static function invoice(array $attributes = []): Invoice
    {
        return (new Invoice)->setRawAttributes($attributes + [
            'id' => (string) Str::uuid(),
            'workspace_id' => (string) Str::uuid(),
            'subscription_id' => (string) Str::uuid(),
            'payment_provider_invoice_id' => 'in_test_'.Str::random(8),
            'amount_cents' => 1900,
            'currency' => 'EUR',
            'status' => InvoiceStatus::Paid,
            'hosted_invoice_url' => null,
            'invoice_pdf_url' => null,
            'issued_at' => now(),
            'paid_at' => now(),
        ], true);
    }
}
