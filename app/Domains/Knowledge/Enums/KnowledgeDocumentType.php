<?php

namespace App\Domains\Knowledge\Enums;

/**
 * Nature du contenu d'un document de connaissance.
 */
enum KnowledgeDocumentType: string
{
    case File = 'file';
    case WebsitePage = 'website_page';
    case Qa = 'qa';

    public function label(): string
    {
        return match ($this) {
            self::File => 'Fichier importé',
            self::WebsitePage => 'Page web',
            self::Qa => 'Question / réponse',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
