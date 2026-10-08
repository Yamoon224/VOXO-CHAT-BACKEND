<?php

namespace App\Domains\Knowledge\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stockage des fichiers importés, sur le disque applicatif (`local`, non
 * public : ces documents ne sont jamais servis directement, seul leur texte
 * extrait l'est, via l'API).
 */
final class KnowledgeFileStorage
{
    public function __construct(private readonly string $disk) {}

    public function store(string $workspaceId, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = (string) Str::uuid().($extension !== '' ? ".{$extension}" : '');

        return $file->storeAs("knowledge/{$workspaceId}", $name, $this->disk);
    }

    public function absolutePath(string $diskPath): string
    {
        return Storage::disk($this->disk)->path($diskPath);
    }

    public function delete(string $diskPath): void
    {
        Storage::disk($this->disk)->delete($diskPath);
    }
}
