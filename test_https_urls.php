<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "=== TESTE DE URLs HTTPS ===\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "ASSET_URL: " . env('ASSET_URL') . "\n";
echo "FORCE_HTTPS: " . (env('FORCE_HTTPS') ? 'true' : 'false') . "\n";
echo "URL Helper: " . url('/') . "\n";
echo "Asset Helper: " . asset('vendor/livewire/livewire.js') . "\n";

// Testar se o arquivo existe
$livewireFile = public_path('vendor/livewire/livewire.js');
echo "Livewire JS existe: " . (file_exists($livewireFile) ? 'SIM' : 'NÃO') . "\n";

if (file_exists($livewireFile)) {
    echo "Tamanho do arquivo: " . filesize($livewireFile) . " bytes\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
