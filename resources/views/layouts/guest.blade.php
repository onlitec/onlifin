<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Icons -->
        <link href="{{ asset('assets/css/remixicon.css') }}" rel="stylesheet">
        
        @livewireStyles
        
        <style>
            body {
                margin: 0;
                padding: 0;
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .auth-container {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                width: 100%;
            }

            /* Estilos globais para autenticação */
            .auth-card {
                background: white;
                width: 100%;
                max-width: 400px;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                border: 1px solid #e5e7eb;
                backdrop-filter: blur(10px);
            }

            .auth-logo h1 {
                font-size: 28px;
                font-weight: 700;
                color: #111827;
                text-align: center;
                margin-bottom: 30px;
            }

            .auth-title {
                font-size: 32px;
                font-weight: 700;
                color: #111827;
                text-align: center;
                margin: 0 0 32px 0;
                letter-spacing: -0.025em;
            }

            .auth-subtitle {
                font-size: 14px;
                color: #6b7280;
                text-align: center;
                margin-bottom: 24px;
            }

            .auth-input-group {
                margin-bottom: 20px;
            }

            .auth-input {
                width: 100%;
                padding: 16px 20px;
                font-size: 16px;
                border: 2px solid #e5e7eb;
                border-radius: 12px;
                box-sizing: border-box;
                background: #f9fafb;
                transition: all 0.2s ease;
                color: #374151;
            }

            .auth-input:focus {
                outline: none;
                border-color: #6366f1;
                background: white;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            }

            .auth-input::placeholder {
                color: #9ca3af;
                font-weight: 500;
            }

            .auth-button {
                width: 100%;
                padding: 16px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 12px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                margin-bottom: 24px;
                transition: all 0.2s ease;
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            }

            .auth-button:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
            }

            .auth-button:active {
                transform: translateY(0);
            }

            .auth-link {
                color: #6366f1;
                text-decoration: none;
                font-weight: 500;
            }

            .auth-link:hover {
                text-decoration: underline;
            }

            .auth-divider {
                text-align: center;
                margin: 24px 0;
                position: relative;
            }

            .auth-divider::before {
                content: '';
                position: absolute;
                top: 50%;
                left: 0;
                right: 0;
                height: 1px;
                background: #e5e7eb;
            }

            .auth-divider span {
                background: white;
                padding: 0 16px;
                color: #9ca3af;
                font-size: 14px;
                font-weight: 500;
                position: relative;
                z-index: 1;
            }

            .auth-button-secondary {
                display: block;
                width: 100%;
                padding: 12px;
                background-color: white;
                color: #374151;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 500;
                text-align: center;
                text-decoration: none;
                box-sizing: border-box;
            }

            .auth-button-secondary:hover {
                background-color: #f9fafb;
            }

            .auth-error {
                color: #dc2626;
                font-size: 14px;
                margin-top: 6px;
                display: block;
                font-weight: 500;
            }

            /* Responsividade */
            @media (max-width: 480px) {
                .auth-container {
                    padding: 16px;
                }
                
                .auth-card {
                    padding: 24px;
                }
                
                .auth-title {
                    font-size: 28px;
                    margin-bottom: 24px;
                }
                
                .auth-input {
                    padding: 14px 18px;
                    font-size: 16px;
                }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="auth-container">
            {{ $slot }}
        </div>

        @livewireScripts
    </body>
</html> 