<div class="auth-card" x-data="{ showPassword: false }">
    <!-- Título -->
    <h2 class="auth-title">Entrar</h2>

    <form wire:submit.prevent="authenticate">
        @csrf
        <!-- Email -->
        <div class="auth-input-group">
            <div class="input-with-icon">
                <i class="ri-mail-line input-icon"></i>
                <input 
                    wire:model="email" 
                    type="email" 
                    class="auth-input with-icon" 
                    placeholder="E-mail"
                    required
                >
            </div>
            @error('email') 
                <span class="auth-error">{{ $message }}</span>
            @enderror
        </div>

        <!-- Senha -->
        <div class="auth-input-group">
            <div class="input-with-icon">
                <i class="ri-lock-line input-icon"></i>
                <input 
                    wire:model="password" 
                    :type="showPassword ? 'text' : 'password'" 
                    class="auth-input with-icon" 
                    placeholder="Senha"
                    required
                >
                <button 
                    type="button"
                    @click="showPassword = !showPassword"
                    class="password-toggle"
                >
                    <i :class="showPassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                </button>
            </div>
            @error('password') 
                <span class="auth-error">{{ $message }}</span>
            @enderror
        </div>

        <!-- Lembrar-me e Esqueceu senha -->
        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center">
                <input type="checkbox" class="form-checkbox h-4 w-4 text-indigo-600 rounded border-gray-300">
                <span class="ml-2 text-sm text-gray-600">Lembrar-me</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-sm text-gray-600 hover:text-indigo-600">
                Esqueceu?
            </a>
        </div>

        <!-- Botão Login -->
        <button type="submit" class="auth-button">
            Entrar
        </button>
    </form>

    <!-- Divisor -->
    <div class="auth-divider">
        <span>ou acesse rapidamente</span>
    </div>

    <!-- Social Login Providers -->
    <div class="social-buttons">
        <x-social-providers action="login" />
    </div>

    <style>
        .auth-card {
            background: white;
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .auth-title {
            font-size: 32px;
            font-weight: 700;
            color: #111827;
            text-align: center;
            margin: 0 0 32px 0;
            letter-spacing: -0.025em;
        }

        .auth-input-group {
            margin-bottom: 20px;
        }

        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            color: #9ca3af;
            font-size: 18px;
            z-index: 1;
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

        .auth-input.with-icon {
            padding-left: 50px;
            padding-right: 50px;
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

        .password-toggle {
            position: absolute;
            right: 16px;
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: #6366f1;
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

        .social-buttons {
            margin-bottom: 24px;
        }

        .auth-error {
            color: #dc2626;
            font-size: 14px;
            margin-top: 6px;
            display: block;
            font-weight: 500;
        }

        .form-checkbox {
            accent-color: #6366f1;
        }

        /* Melhorias responsivas */
        @media (max-width: 480px) {
            .auth-card {
                padding: 24px;
                margin: 16px;
            }
            
            .auth-title {
                font-size: 28px;
                margin-bottom: 24px;
            }
            
            .auth-input {
                padding: 14px 18px;
                font-size: 16px;
            }
            
            .auth-input.with-icon {
                padding-left: 46px;
                padding-right: 46px;
            }
            
            .input-icon {
                left: 14px;
            }
            
            .password-toggle {
                right: 14px;
            }
        }
    </style>
</div>