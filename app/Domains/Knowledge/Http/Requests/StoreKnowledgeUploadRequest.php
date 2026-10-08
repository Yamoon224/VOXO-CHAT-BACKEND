<?php

namespace App\Domains\Knowledge\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreKnowledgeUploadRequest extends FormRequest
{
    /** Extensions acceptées : fichiers texte, bureautique et tableurs (section 2.1). */
    public const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'txt', 'md', 'markdown', 'csv', 'xlsx', 'xls'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => [
                'required',
                'file',
                'max:'.(int) config('knowledge.max_upload_kb'),
                function (string $attribute, mixed $value, callable $fail): void {
                    if ($value instanceof UploadedFile
                        && ! in_array(strtolower($value->getClientOriginalExtension()), self::ALLOWED_EXTENSIONS, true)) {
                        $fail('Ce type de fichier n\'est pas pris en charge.');
                    }
                },
            ],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nom de la source', 'files' => 'fichiers'];
    }
}
