<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentação da API - Onlifin</title>
    <link href="{{ asset('assets/swagger-ui/swagger-ui.css') }}" rel="stylesheet">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="{{ asset('assets/swagger-ui/swagger-ui-bundle.js') }}"></script>
    <script>
        const ui = SwaggerUIBundle({
            url: '/api/docs/openapi',
            dom_id: '#swagger-ui',
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIBundle.SwaggerUIStandalonePreset
            ],
            layout: "BaseLayout",
            deepLinking: true,
            showExtensions: true,
            showCommonExtensions: true
        });
    </script>
</body>
</html>
