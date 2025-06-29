<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallController extends Controller
{
    /**
     * Executa a instalação: migrations, seeders e marca como instalado.
     */
    public function install(Request $request)
    {
        // Se já instalado, redireciona ao login
        if (File::exists(storage_path('installed.flag'))) {
            return redirect()->route('login');
        }

        // Executa migrações e seeders
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);

        // Cria flag de instalação
        File::put(storage_path('installed.flag'), now());

        // Define credenciais para exibir
        $credentials = [];
        $credentials[] = ['email' => 'admin@onlifin.com.br', 'password' => 'admin123'];
        $defaultEmail = env('DEFAULT_ADMIN_EMAIL', 'admin@admin.com');
        $defaultPassword = env('DEFAULT_ADMIN_PASSWORD', 'AdminMudar');
        if ($defaultEmail !== 'admin@onlifin.com.br') {
            $credentials[] = ['email' => $defaultEmail, 'password' => $defaultPassword];
        }

        // Exibe página com credenciais
        return view('install_complete', ['credentials' => $credentials]);
    }
} 