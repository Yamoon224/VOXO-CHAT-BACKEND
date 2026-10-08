<?php

namespace App\Domains\Knowledge\Extractors;

use App\Domains\Knowledge\Contracts\TextExtractorContract;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class SpreadsheetTextExtractor implements TextExtractorContract
{
    private const MIME_TYPES = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'text/csv',
    ];

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, self::MIME_TYPES, true);
    }

    public function extract(string $absolutePath): string
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $lines = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            foreach ($sheet->toArray(null, true, true, false) as $row) {
                $cells = array_filter($row, fn (mixed $cell) => $cell !== null && $cell !== '');

                if ($cells !== []) {
                    $lines[] = implode(' | ', array_map('strval', $cells));
                }
            }
        }

        return implode("\n", $lines);
    }
}
