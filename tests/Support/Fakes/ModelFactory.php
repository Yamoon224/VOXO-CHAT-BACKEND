<?php

namespace Tests\Support\Fakes;

use App\Domains\Knowledge\Enums\KnowledgeDocumentStatus;
use App\Domains\Knowledge\Enums\KnowledgeDocumentType;
use App\Domains\Knowledge\Enums\KnowledgeSourceType;
use App\Domains\Knowledge\Enums\RecrawlFrequency;
use App\Domains\Shared\Enums\WorkspaceRole;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeSource;
use App\Models\User;
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
}
