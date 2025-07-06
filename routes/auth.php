<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {

    // Google OAuth routes (mantido para compatibilidade)
    Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle'])
        ->name('auth.google');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])
        ->name('auth.google.callback');

    // Social Auth routes (Hybridauth)
    Route::get('auth/social/{provider}', [SocialAuthController::class, 'redirectToProvider'])
        ->name('auth.social.redirect');
    Route::get('auth/social/callback', [SocialAuthController::class, 'handleCallback'])
        ->name('auth.social.callback');

    // 2FA verification routes
    Route::get('2fa/verify', [TwoFactorController::class, 'showVerifyForm'])
        ->name('2fa.verify');
    Route::post('2fa/verify', [TwoFactorController::class, 'verify'])
        ->name('2fa.verify.post');
    Route::get('2fa/recovery', [TwoFactorController::class, 'showRecoveryForm'])
        ->name('2fa.recovery');
    Route::post('2fa/recovery', [TwoFactorController::class, 'verifyRecovery'])
        ->name('2fa.recovery.post');
});

Route::middleware('auth')->group(function () {

    // 2FA management routes (authenticated users only)
    Route::get('2fa/setup', [TwoFactorController::class, 'showSetupForm'])
        ->name('2fa.setup');
    Route::post('2fa/setup', [TwoFactorController::class, 'confirmSetup'])
        ->name('2fa.setup.confirm');
    Route::post('2fa/disable', [TwoFactorController::class, 'disable'])
        ->name('2fa.disable');
    Route::post('2fa/recovery-codes', [TwoFactorController::class, 'generateRecoveryCodes'])
        ->name('2fa.recovery-codes');

    // Google account management (mantido para compatibilidade)
    Route::post('auth/google/unlink', [GoogleAuthController::class, 'unlinkGoogle'])
        ->name('auth.google.unlink');

    // Social Auth management
    Route::post('auth/social/{provider}/unlink', [SocialAuthController::class, 'unlinkProvider'])
        ->name('auth.social.unlink');
}); 