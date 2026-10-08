<?php

namespace App\Domains\Knowledge\Enums;

/**
 * Statut de traitement d'un document, visible par l'équipe pendant toute
 * la chaîne d'ingestion (extraction, découpage, embeddings, indexation).
 */
enum KnowledgeDocumentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Indexed = 'indexed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Processing => 'En cours',
            self::Indexed => 'Indexé',
            self::Failed => 'Échec',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
