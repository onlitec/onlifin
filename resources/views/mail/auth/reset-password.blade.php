<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha - Onlifin</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #374151;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .email-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .logo {
            color: #ffffff;
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .email-body {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 20px 0;
        }
        .message {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 30px;
        }
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            transition: all 0.2s ease;
        }
        .action-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }
        .security-notice {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        .security-notice h4 {
            color: #92400e;
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: 600;
        }
        .security-notice p {
            color: #92400e;
            margin: 0;
            font-size: 14px;
        }
        .email-footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer-text {
            color: #6b7280;
            font-size: 14px;
            margin: 0;
        }
        .link-alternative {
            background-color: #f3f4f6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #d1d5db;
        }
        .link-alternative p {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #6b7280;
        }
        .link-url {
            word-break: break-all;
            color: #6366f1;
            font-size: 13px;
            font-family: monospace;
        }
        .expiry-info {
            background-color: #eff6ff;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .expiry-info p {
            margin: 0;
            color: #1e40af;
            font-size: 14px;
        }
        @media (max-width: 600px) {
            .email-header, .email-body, .email-footer {
                padding: 20px;
            }
            .greeting {
                font-size: 20px;
            }
            .action-button {
                display: block;
                text-align: center;
                margin: 20px 0;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1 class="logo">Onlifin</h1>
        </div>

        <!-- Body -->
        <div class="email-body">
            <h2 class="greeting">Olá, {{ $user->name }}!</h2>
            
            <p class="message">
                Você solicitou a redefinição de sua senha no Onlifin. Clique no botão abaixo para criar uma nova senha:
            </p>

            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="action-button">
                    Redefinir Minha Senha
                </a>
            </div>

            <div class="expiry-info">
                <p><strong>⏰ Importante:</strong> Este link expira em {{ $expireMinutes }} minutos por segurança.</p>
            </div>

            <div class="security-notice">
                <h4>🔒 Aviso de Segurança</h4>
                <p>Se você não solicitou esta redefinição de senha, ignore este email. Sua senha permanecerá inalterada.</p>
            </div>

            <div class="link-alternative">
                <p>Se o botão não funcionar, copie e cole o link abaixo no seu navegador:</p>
                <div class="link-url">{{ $resetUrl }}</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p class="footer-text">
                Este email foi enviado automaticamente pelo sistema Onlifin.<br>
                Por favor, não responda a este email.
            </p>
            <p class="footer-text" style="margin-top: 15px;">
                © {{ date('Y') }} Onlifin. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html> 