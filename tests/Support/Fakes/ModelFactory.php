<?php

namespace Tests\Support\Fakes;

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
use App\Domains\Widget\Enums\WidgetPosition;
use App\Models\AssistantSettings;
use App\Models\CannedResponse;
use App\Models\Conversation;
use App\Models\Invoice;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeSource;
use App\Models\Message;
use App\Models\Plan;
use App\Models\Subscription;
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
            'issued_at' => now(),
            'paid_at' => now(),
        ], true);
    }
}
