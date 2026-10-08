<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentation de l'API VOXO</title>

    {{--
        Swagger UI est servi depuis nos propres assets et non depuis un CDN :
        aucune ressource tierce ne peut être substituée à notre insu. Les
        fichiers sont copiés depuis swagger-ui-dist par `composer run docs:assets`.
    --}}
    <link rel="stylesheet" href="{{ asset('vendor/swagger-ui/swagger-ui.css') }}">

    <style>
        /* La feuille de style de Swagger UI est écrite en clair et n'expose
           aucune variable : la page assume un thème clair, et un seul. */
        :root, .swagger-ui { color-scheme: light; }
        body { margin: 0; background: #fafafa; font: 15px/1.6 system-ui, "Segoe UI", Roboto, sans-serif; }
        .masthead { border-bottom: 1px solid #e5e7eb; background: #fff; }
        .masthead .inner { max-width: 1180px; margin: 0 auto; padding: 32px 24px 24px; }
        .masthead h1 { margin: 0; font-size: 26px; letter-spacing: -.02em; }
        .masthead p { max-width: 68ch; margin: .5rem 0 0; color: #4b5563; }
        #swagger-ui { max-width: 1180px; margin: 0 auto; padding: 8px 14px 72px; }
        .swagger-ui .topbar { display: none; }
    </style>
</head>
<body>
    <header class="masthead">
        <div class="inner">
            <h1>API VOXO</h1>
            <p>
                Contrat de l'API REST : endpoints, paramètres, authentification, codes HTTP et forme des
                erreurs. Un test automatisé vérifie que cette spécification couvre toutes les routes exposées.
            </p>
        </div>
    </header>

    <div id="swagger-ui"></div>

    <script src="{{ asset('vendor/swagger-ui/swagger-ui-bundle.js') }}"></script>
    <script>
        window.addEventListener('load', function () {
            window.ui = SwaggerUIBundle({
                url: @json(url('/docs/openapi.json')),
                dom_id: '#swagger-ui',
                deepLinking: true,
                docExpansion: 'list',
                defaultModelsExpandDepth: 0,
                persistAuthorization: true,
            });
        });
    </script>
</body>
</html>
