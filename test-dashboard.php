<?php

require_once 'vendor/autoload.php';

// Carregar Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "🔍 Teste do Dashboard - Onlifin\n";
echo "===============================\n\n";

try {
    // Fazer login como admin
    $user = User::where('email', 'admin@onlifin.com')->first();
    
    if (!$user) {
        echo "❌ Usuário admin não encontrado\n";
        exit(1);
    }
    
    Auth::login($user);
    echo "✅ Login realizado: {$user->name}\n";
    
    // Testar se consegue acessar o controller do dashboard
    $controller = new \App\Http\Controllers\DashboardController();
    
    // Criar uma request fake
    $request = new \Illuminate\Http\Request();
    $request->merge(['period' => 'current_month']);
    
    echo "🔄 Testando dashboard controller...\n";
    
    $response = $controller->index($request);
    
    if ($response instanceof \Illuminate\View\View) {
        echo "✅ Dashboard carregou com sucesso!\n";
        echo "📊 View: {$response->getName()}\n";
        
        $data = $response->getData();
        echo "📈 Dados disponíveis:\n";
        echo "   - Saldo atual: " . (isset($data['currentBalance']) ? 'OK' : 'FALTANDO') . "\n";
        echo "   - Receitas: " . (isset($data['totalIncomePeriod']) ? 'OK' : 'FALTANDO') . "\n";
        echo "   - Despesas: " . (isset($data['totalExpensesPeriod']) ? 'OK' : 'FALTANDO') . "\n";
        echo "   - Gráficos: " . (isset($data['expenseChartData']) ? 'OK' : 'FALTANDO') . "\n";
        
    } else {
        echo "⚠️ Dashboard retornou resposta inesperada\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erro no dashboard: " . $e->getMessage() . "\n";
    echo "📍 Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    if (strpos($e->getMessage(), 'DATE_FORMAT') !== false) {
        echo "🔧 Erro relacionado a DATE_FORMAT - verificar correções SQLite\n";
    }
}

echo "\n🏁 Teste concluído!\n";
