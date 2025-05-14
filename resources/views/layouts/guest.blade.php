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

        <!-- Styles -->
        <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
        
        @livewireStyles
        
        <style>
            body {
                margin: 0;
                padding: 0;
                font-family: 'Inter', sans-serif;
                background-color: #f3f4f6;
                min-height: 100vh;
            }
            .auth-container {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .auth-card {
                background: white;
                width: 100%;
                max-width: 400px;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .auth-logo h1 {
                font-size: 28px;
                font-weight: 700;
                color: #111827;
                text-align: center;
                margin-bottom: 30px;
            }
            .auth-title {
                font-size: 24px;
                font-weight: 600;
                color: #111827;
                text-align: center;
                margin: 0 0 8px 0;
            }
            .auth-subtitle {
                font-size: 14px;
                color: #6b7280;
                text-align: center;
                margin-bottom: 24px;
            }
            .auth-input {
                width: 100%;
                padding: 12px 16px;
                font-size: 14px;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                box-sizing: border-box;
                margin-bottom: 16px;
            }
            .auth-input:focus {
                outline: none;
                border-color: #4f46e5;
                box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            }
            .auth-button {
                width: 100%;
                padding: 12px;
                background-color: #4f46e5;
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                margin-bottom: 24px;
            }
            .auth-button:hover {
                background-color: #4338ca;
            }
            .auth-link {
                color: #4f46e5;
                text-decoration: none;
                font-weight: 500;
            }
            .auth-link:hover {
                text-decoration: underline;
            }
            .auth-divider {
                text-align: center;
                font-size: 14px;
                color: #6b7280;
                margin: 24px 0;
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
                margin-top: 4px;
                display: block;
            }
            .auth-input-group {
                position: relative;
                margin-bottom: 20px;
            }
            
            .auth-input-icon {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                cursor: pointer;
                padding: 4px;
                color: #6b7280;
            }
            
            .auth-input-icon:hover {
                color: #4f46e5;
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