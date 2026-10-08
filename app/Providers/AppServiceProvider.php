<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        /*
         * Les relations doivent être chargées explicitement.
         *
         * Sans cela, un accès à une relation non chargée déclenche une requête
         * silencieuse par ligne (N+1). Actif hors production uniquement : en
         * production, une relation oubliée doit dégrader la performance, pas
         * casser la page.
         */
        Model::preventLazyLoading(! $this->app->isProduction());

        // Une écriture sur un attribut absent de `$fillable` est une erreur de
        // développement, pas une donnée à ignorer en silence.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}
