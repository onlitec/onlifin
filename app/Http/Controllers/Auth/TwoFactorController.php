<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Hash;

class TwoFactorController extends Controller
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Exibe a tela de verificação 2FA
     */
    public function showVerifyForm()
    {
        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.2fa.verify');
    }

    /**
     * Verifica o código 2FA fornecido
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $userId = session('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('login');
        }

        $code = $request->code;
        $valid = $this->google2fa->verifyKey($user->two_factor_secret, $code);

        if ($valid) {
            // Código válido, faz login
            Auth::login($user);
            session()->regenerate();
            session()->forget('2fa_user_id');

            Log::info('Login 2FA realizado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return redirect()->intended('/dashboard')->with('success', 'Login realizado com sucesso!');
        }

        return back()->withErrors(['code' => 'Código inválido. Tente novamente.']);
    }

    /**
     * Verifica código de recuperação
     */
    public function verifyRecovery(Request $request)
    {
        $request->validate([
            'recovery_code' => 'required|string',
        ]);

        $userId = session('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->useRecoveryCode($request->recovery_code)) {
            // Código de recuperação válido, faz login
            Auth::login($user);
            session()->regenerate();
            session()->forget('2fa_user_id');

            Log::info('Login com código de recuperação realizado', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return redirect()->intended('/dashboard')->with('warning', 'Login realizado com código de recuperação. Considere gerar novos códigos.');
        }

        return back()->withErrors(['recovery_code' => 'Código de recuperação inválido.']);
    }

    /**
     * Exibe a tela de configuração do 2FA
     */
    public function showSetupForm()
    {
        $user = Auth::user();
        
        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit')->with('info', 'Autenticação em duas etapas já está habilitada.');
        }

        // Gera um novo secret se não existir
        if (!$user->two_factor_secret) {
            $secret = $this->google2fa->generateSecretKey();
            $user->update(['two_factor_secret' => $secret]);
        }

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->two_factor_secret
        );

        return view('auth.2fa.setup', compact('qrCodeUrl'));
    }

    /**
     * Confirma a configuração do 2FA
     */
    public function confirmSetup(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        $code = $request->code;
        
        $valid = $this->google2fa->verifyKey($user->two_factor_secret, $code);

        if ($valid) {
            // Habilita 2FA e gera códigos de recuperação
            $user->update([
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => now(),
            ]);

            $recoveryCodes = $user->generateRecoveryCodes();

            Log::info('2FA habilitado para usuário', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return view('auth.2fa.recovery-codes', compact('recoveryCodes'));
        }

        return back()->withErrors(['code' => 'Código inválido. Tente novamente.']);
    }

    /**
     * Desabilita 2FA
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Senha incorreta.']);
        }

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        Log::info('2FA desabilitado para usuário', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return back()->with('success', 'Autenticação em duas etapas desabilitada com sucesso.');
    }

    /**
     * Gera novos códigos de recuperação
     */
    public function generateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Senha incorreta.']);
        }

        if (!$user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Autenticação em duas etapas não está habilitada.');
        }

        $recoveryCodes = $user->generateRecoveryCodes();

        Log::info('Novos códigos de recuperação gerados', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return view('auth.2fa.recovery-codes', compact('recoveryCodes'));
    }

    /**
     * Exibe a tela de uso de código de recuperação
     */
    public function showRecoveryForm()
    {
        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.2fa.recovery');
    }
}
