<?php

namespace App\Domains\HelpCenter\Http\Controllers;

use App\Domains\HelpCenter\Http\Resources\HelpCenterCategoryResource;
use App\Domains\HelpCenter\Http\Resources\PublicHelpCenterArticleResource;
use App\Domains\HelpCenter\Services\HelpCenterPublicService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Pages publiques du centre d'aide (section 2.10) : pas d'authentification. */
class PublicHelpCenterController extends Controller
{
    public function __construct(private readonly HelpCenterPublicService $helpCenter) {}

    public function categories(Request $request, string $workspace): AnonymousResourceCollection
    {
        return HelpCenterCategoryResource::collection(
            $this->helpCenter->categories($workspace, $this->locale($request)),
        );
    }

    public function articles(Request $request, string $workspace): AnonymousResourceCollection
    {
        return PublicHelpCenterArticleResource::collection(
            $this->helpCenter->articles($workspace, $this->locale($request), $request->string('category_id')->toString() ?: null),
        );
    }

    public function article(Request $request, string $workspace, string $slug): PublicHelpCenterArticleResource
    {
        return new PublicHelpCenterArticleResource(
            $this->helpCenter->article($workspace, $this->locale($request), $slug),
        );
    }

    private function locale(Request $request): string
    {
        $locale = $request->string('locale')->toString();

        return in_array($locale, ['fr', 'en'], true) ? $locale : 'fr';
    }
}
