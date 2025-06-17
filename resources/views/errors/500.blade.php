<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erro no Servidor - Onlifin</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Remix Icons -->
    <link href="{{ asset('assets/css/remixicon.css') }}" rel="stylesheet">
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }
        .error-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
            text-align: center;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            color: #dc2626;
            line-height: 1;
            margin-bottom: 1rem;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        .error-message {
            color: #6b7280;
            margin-bottom: 2rem;
        }
        .error-image {
            max-width: 100%;
            height: auto;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            background-color: #dc2626;
            color: white;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #b91c1c;
        }
        .btn i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body class="antialiased">
    <div class="min-h-screen flex flex-col justify-center items-center p-4">
        <div class="error-container">
            <div class="error-code">500</div>
            <h1 class="error-title">Erro no Servidor</h1>
            <p class="error-message">Desculpe, ocorreu um erro interno no servidor. Nossa equipe técnica já foi notificada e está trabalhando para resolver o problema.</p>
            
            <img src="{{ asset('assets/images/500-illustration.svg') }}" alt="Erro no servidor" class="error-image" onerror="this.style.display='none'">
            
            <div class="flex justify-center">
                <a href="{{ url('/') }}" class="btn">
                    <i class="ri-home-line"></i> Voltar para o Início
                </a>
            </div>
        </div>
    </div>
</body>
</html> 