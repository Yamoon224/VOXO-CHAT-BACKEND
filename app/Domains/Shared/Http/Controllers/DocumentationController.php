<?php

namespace App\Domains\Shared\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\Yaml\Yaml;

/**
 * Sert la spécification OpenAPI et son interface Swagger UI.
 *
 * La spécification est écrite à la main, en amont du code. Sa cohérence avec
 * les routes réellement enregistrées est vérifiée par
 * `OpenApiSpecificationTest`, ce qui l'empêche de dériver en silence.
 */
class DocumentationController extends Controller
{
    public const SPECIFICATION_PATH = 'openapi/openapi.yaml';

    public function ui(): View
    {
        return view('documentation');
    }

    /** Spécification convertie en JSON, consommée par Swagger UI. */
    public function specification(): JsonResponse
    {
        return response()->json(self::specificationArray());
    }

    /** @return array<string, mixed> */
    public static function specificationArray(): array
    {
        /** @var array<string, mixed> $parsed */
        $parsed = Yaml::parse(File::get(resource_path(self::SPECIFICATION_PATH)));

        return $parsed;
    }
}
