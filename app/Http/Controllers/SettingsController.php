<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Models\Transaction;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use ZipArchive;
use Livewire\WithPagination;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;
use App\Services\FinancialReportAIService;
use App\Services\FinancialDataService;

class SettingsController extends Controller
{
    use WithPagination;

    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin() || $user->hasPermission('manage_settings');

        \Illuminate\Support\Facades\Log::info('Acessando configurações', [
            'user_id' => auth()->id(),
            'is_admin' => $isAdmin,
            'email' => auth()->user()->email,
            'request_path' => request()->path(),
            'request_url' => request()->url()
        ]);
        
        // Passa a lista de usuários para a view, se for admin ou tiver permissão
        $usersForDeletion = $isAdmin ? $this->usersForDeletion() : collect();
        
        return view('settings.index', compact('isAdmin', 'usersForDeletion'));
    }

    public function users()
    {
        $users = User::with('roles')->paginate(10);
        return view('settings.users.index', compact('users'));
    }

    public function createUser()
    {
        $roles = Role::all();
        return view('settings.users.create', compact('roles'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'roles' => 'array',
            'is_active' => 'boolean'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'is_active' => $request->has('is_active')
        ]);

        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        return redirect()->route('settings.users')->with('message', 'Usuário criado com sucesso!');
    }

    public function editUser(User $user)
    {
        $roles = Role::all();
        $userRoles = $user->roles->pluck('id')->toArray();
        return view('settings.users.edit', compact('user', 'roles', 'userRoles'));
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'roles' => 'array',
            'is_active' => 'boolean'
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $request->has('is_active')
        ]);

        if ($request->has('password') && !empty($request->password)) {
            $request->validate([
                'password' => 'min:8|confirmed'
            ]);
            $user->password = bcrypt($request->password);
            $user->save();
        }

        $user->roles()->sync($request->roles ?? []);

        return redirect()->route('settings.users')->with('message', 'Usuário atualizado com sucesso!');
    }

    public function deleteUser(User $user)
    {
        $user->delete();
        return redirect()->route('settings.users')->with('message', 'Usuário excluído com sucesso!');
    }

    public function roles()
    {
        $roles = Role::with('permissions')->get();
        return view('settings.roles.index', compact('roles'));
    }

    public function createRole()
    {
        $permissions = Permission::all();
        return view('settings.roles.create', compact('permissions'));
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3|unique:roles,name',
            'description' => 'nullable|string',
            'permissions' => 'array'
        ]);

        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return redirect()->route('settings.roles')->with('message', 'Perfil criado com sucesso!');
    }

    public function editRole(Role $role)
    {
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        return view('settings.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function updateRole(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|min:3|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
            'permissions' => 'array'
        ]);

        $role->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        $role->permissions()->sync($request->permissions ?? []);

        return redirect()->route('settings.roles')->with('message', 'Perfil atualizado com sucesso!');
    }

    public function deleteRole(Role $role)
    {
        $role->delete();
        return redirect()->route('settings.roles')->with('message', 'Perfil excluído com sucesso!');
    }

    public function permissions()
    {
        $permissions = Permission::paginate(10);
        $categories = $this->getPermissionCategories();
        return view('settings.permissions.index', compact('permissions', 'categories'));
    }

    public function createPermission()
    {
        $categories = $this->getPermissionCategories();
        return view('settings.permissions.create', compact('categories'));
    }

    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3|unique:permissions,name',
            'description' => 'nullable|string',
            'category' => 'required|string'
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category
        ]);

        return redirect()->route('settings.permissions')->with('message', 'Permissão criada com sucesso!');
    }

    public function editPermission(Permission $permission)
    {
        $categories = $this->getPermissionCategories();
        return view('settings.permissions.edit', compact('permission', 'categories'));
    }

    public function updatePermission(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|min:3|unique:permissions,name,' . $permission->id,
            'description' => 'nullable|string',
            'category' => 'required|string'
        ]);

        $permission->update([
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category
        ]);

        return redirect()->route('settings.permissions')->with('message', 'Permissão atualizada com sucesso!');
    }

    public function deletePermission(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('settings.permissions')->with('message', 'Permissão excluída com sucesso!');
    }

    /**
     * Retorna as categorias de permissões para agrupar na UI
     */
    private function getPermissionCategories()
    {
        return [
            'users' => 'Usuários',
            'roles' => 'Perfis',
            'transactions' => 'Transações',
            'categories' => 'Categorias',
            'accounts' => 'Contas',
            'reports' => 'Relatórios',
            'system' => 'Sistema'
        ];
    }

    public function reports(Request $request)
    {
        $user = auth()->user();
        $userId = $user->id;
        // Obter limites de data a partir dos parâmetros ou mês atual
        $startParam = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endParam = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $currentMonthStart = Carbon::parse($startParam)->startOfDay();
        $currentMonthEnd = Carbon::parse($endParam)->endOfDay();

        // 1. Despesas por Categoria (Mês Atual)
        $expensesByCategory = Transaction::when(!$user->isAdmin(), function($query) use ($userId) {
            $query->where('transactions.user_id', $userId);
        })
            ->where('transactions.type', 'expense')
            ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.name as category_name', DB::raw('SUM(transactions.amount) as total_amount'))
            ->groupBy('categories.name')
            ->orderBy('total_amount', 'desc')
            ->get();
            
        // Preparar dados para Chart.js (Categoria)
        $categoryLabels = $expensesByCategory->pluck('category_name');
        $categoryData = $expensesByCategory->pluck('total_amount')->map(fn($amount) => $amount / 100); // Convert cents to currency unit

        // 2. Despesas por Conta (Mês Atual)
        $expensesByAccount = Transaction::when(!$user->isAdmin(), function($query) use ($userId) {
            $query->where('transactions.user_id', $userId);
        })
            ->where('transactions.type', 'expense')
            ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->select('accounts.name as account_name', DB::raw('SUM(transactions.amount) as total_amount'))
            ->groupBy('accounts.name')
            ->orderBy('total_amount', 'desc')
            ->get();

        // Preparar dados para Chart.js (Conta)
        $accountLabels = $expensesByAccount->pluck('account_name');
        $accountData = $expensesByAccount->pluck('total_amount')->map(fn($amount) => $amount / 100); // Convert cents to currency unit

        // 3. Receitas por Conta (Mês Atual)
        $incomesByAccount = Transaction::when(!$user->isAdmin(), function($query) use ($userId) {
            $query->where('transactions.user_id', $userId);
        })
            ->where('transactions.type', 'income')
            ->whereBetween('transactions.date', [$currentMonthStart, $currentMonthEnd])
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->select('accounts.name as account_name', DB::raw('SUM(transactions.amount) as total_amount'))
            ->groupBy('accounts.name')
            ->orderBy('total_amount', 'desc')
            ->get();

        // Preparar dados para Chart.js (Receitas por Conta)
        if ($incomesByAccount->isEmpty()) {
            $incomeAccountLabels = [];
            $incomeAccountData = [];
        } else {
            $incomeAccountLabels = $incomesByAccount->pluck('account_name');
            $incomeAccountData = $incomesByAccount->pluck('total_amount')->map(fn($amount) => $amount / 100);
        }

        // 4. Transferências por Conta (Mês Atual)
        // Para transferências, precisamos identificar pares de transações (despesa e receita) com o mesmo valor e data
        $transfersQuery = DB::table('transactions as t1')
            ->join('transactions as t2', function ($join) {
                $join->on('t1.amount', '=', 't2.amount')
                    ->on('t1.date', '=', 't2.date')
                    ->on('t1.description', '=', 't2.description');
            })
            ->when(!$user->isAdmin(), function($query) use ($userId) {
                $query->where('t1.user_id', $userId)
                      ->where('t2.user_id', $userId);
            })
            ->where('t1.type', 'expense')
            ->where('t2.type', 'income')
            ->whereBetween('t1.date', [$currentMonthStart, $currentMonthEnd])
            ->join('accounts as a1', 't1.account_id', '=', 'a1.id')
            ->join('accounts as a2', 't2.account_id', '=', 'a2.id')
            ->select(
                'a1.name as origin_account',
                'a2.name as destination_account',
                't1.amount',
                't1.date',
                't1.description'
            )
            ->orderBy('t1.date', 'desc')
            ->get();

        // Preparar dados para Chart.js (Transferências)
        $transferDetails = [];
        $transferAccountsMap = [];
        
        // Verificar se há transferências encontradas
        if ($transfersQuery->isEmpty()) {
            $transferDetails = [];
            $transferAccountsMap = [];
        } else {
        
        foreach ($transfersQuery as $transfer) {
            $transferDetails[] = [
                'origin' => $transfer->origin_account,
                'destination' => $transfer->destination_account,
                'amount' => $transfer->amount,
                'date' => $transfer->date,
                'description' => $transfer->description
            ];
            
            // Contabilizar transferências por conta (tanto origem quanto destino)
            if (!isset($transferAccountsMap[$transfer->origin_account])) {
                $transferAccountsMap[$transfer->origin_account] = 0;
            }
            if (!isset($transferAccountsMap[$transfer->destination_account])) {
                $transferAccountsMap[$transfer->destination_account] = 0;
            }
            
            $transferAccountsMap[$transfer->origin_account] += $transfer->amount;
            $transferAccountsMap[$transfer->destination_account] += $transfer->amount;
        }
        }
        
        // Converter para arrays para o gráfico
        $transferAccountLabels = !empty($transferAccountsMap) ? array_keys($transferAccountsMap) : [];
        $transferAccountData = !empty($transferAccountsMap) ? array_map(fn($amount) => $amount / 100, array_values($transferAccountsMap)) : [];

        // 5. Receitas por Categoria (Mês Atual)
        $incomesByCategory = Transaction::when(!$user->isAdmin(), function($query) use ($userId) {
            $query->where('transactions.user_id', $userId);
        })
            ->where('transactions.type', 'income')
            ->whereBetween('transactions.date', [$currentMonthStart, $currentMonthEnd])
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.name as category_name', DB::raw('SUM(transactions.amount) as total_amount'))
            ->groupBy('categories.name')
            ->orderBy('total_amount', 'desc')
            ->get();
        $incomeCategoryLabels = $incomesByCategory->pluck('category_name');
        $incomeCategoryData = $incomesByCategory->pluck('total_amount')->map(fn($amount) => $amount / 100);

        // 6. Receitas Recebidas e Pendentes (Mês Atual)
        $incomeByStatus = Transaction::when(!$user->isAdmin(), function($query) use ($userId) {
            $query->where('transactions.user_id', $userId);
        })
            ->where('transactions.type', 'income')
            ->whereBetween('transactions.date', [$currentMonthStart, $currentMonthEnd])
            ->select('status', DB::raw('SUM(transactions.amount) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $paidIncome = ($incomeByStatus['paid'] ?? 0) / 100;
        $pendingIncome = ($incomeByStatus['pending'] ?? 0) / 100;
        $forecastIncome = $paidIncome + $pendingIncome;

        // Total de Despesas (Mês Atual)
        $totalExpenses = Transaction::when(!$user->isAdmin(), function($query) use ($userId) {
            $query->where('transactions.user_id', $userId);
        })
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.date', [$currentMonthStart, $currentMonthEnd])
            ->sum('transactions.amount') / 100;

        // Despesas Pagas (Mês Atual) e Saldo Líquido
        $paidExpenses = Transaction::when(!$user->isAdmin(), function($query) use ($userId) {
            $query->where('transactions.user_id', $userId);
        })
            ->where('transactions.type', 'expense')
            ->where('transactions.status', 'paid')
            ->whereBetween('transactions.date', [$currentMonthStart, $currentMonthEnd])
            ->sum('transactions.amount') / 100;
        $netBalance = $paidIncome - $paidExpenses;

        // 7. Detalhamento de Despesas (últimas 50 no período)
        $detailedExpenses = Transaction::when(!$user->isAdmin(), function($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->where('type', 'expense')
            ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
            ->with(['category', 'account'])
            ->orderByDesc('date')
            ->limit(50)
            ->get();

        // Detalhamento de Receitas (últimas 50 no período)
        $detailedIncomes = Transaction::when(!$user->isAdmin(), function($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->where('type', 'income')
            ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
            ->with(['category', 'account'])
            ->orderByDesc('date')
            ->limit(50)
            ->get();

        // Comparativo diário de Receitas vs Despesas
        $dateLabels = [];
        $rawDates = [];
        $current = $currentMonthStart->copy();
        while ($current->lte($currentMonthEnd)) {
            $rawDates[] = $current->format('Y-m-d');
            $dateLabels[] = $current->format('d/m');
            $current->addDay();
        }
        $incomeByDateRaw = Transaction::when(!$user->isAdmin(), function($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->where('type', 'income')
            ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
            ->select(DB::raw('DATE(date) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');
        $expenseByDateRaw = Transaction::when(!$user->isAdmin(), function($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->where('type', 'expense')
            ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
            ->select(DB::raw('DATE(date) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');
        $incomeSeries = [];
        $expenseSeries = [];
        foreach ($rawDates as $d) {
            $incomeSeries[] = ($incomeByDateRaw[$d] ?? 0) / 100;
            $expenseSeries[] = ($expenseByDateRaw[$d] ?? 0) / 100;
        }

        // Projeção de Fluxo de Caixa Futuro (7 dias)
        $dailyDiff = [];
        foreach ($incomeSeries as $idx => $inc) {
            $dailyDiff[] = $inc - ($expenseSeries[$idx] ?? 0);
        }
        $avgDailyNet = count($dailyDiff) ? array_sum($dailyDiff) / count($dailyDiff) : 0;
        $projectionDays = 7;
        $projectionLabels = [];
        $projectionValues = [];
        for ($i = 1; $i <= $projectionDays; $i++) {
            $date = Carbon::parse($currentMonthEnd)->addDays($i);
            $projectionLabels[] = $date->format('d/m');
            $projectionValues[] = $netBalance + $avgDailyNet * $i;
        }

        // Retornar view com dados e filtros
        return view('settings.reports.index', compact(
            'detailedExpenses',
            'detailedIncomes',
            'categoryLabels',
            'categoryData',
            'accountLabels',
            'accountData',
            'incomeAccountLabels',
            'incomeAccountData',
            'transferAccountLabels',
            'transferAccountData',
            'transferDetails',
            'paidIncome',
            'pendingIncome',
            'forecastIncome',
            'totalExpenses',
            'netBalance',
            'dateLabels',
            'incomeSeries',
            'expenseSeries',
            'incomeCategoryLabels',
            'incomeCategoryData',
            'projectionLabels',
            'projectionValues',
            'startParam',
            'endParam'
        ));
    }

    public function notifications()
    {
        $user = auth()->user();
        return view('settings.notifications.index', compact('user'));
    }

    public function updateNotifications(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'due_date_notifications' => 'boolean'
        ]);
        
        $user->update([
            'email_notifications' => $request->has('email_notifications'),
            'push_notifications' => $request->has('push_notifications'),
            'due_date_notifications' => $request->has('due_date_notifications')
        ]);
        
        return redirect()->route('settings.notifications')->with('success', 'Configurações de notificação atualizadas com sucesso!');
    }

    public function profile()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
        ]);
        
        $user->name = $request->name;
        $user->email = $request->email;
        
        if ($request->filled('password')) {
            $request->validate([
                'password' => 'string|min:8|confirmed',
            ]);
            
            $user->password = bcrypt($request->password);
        }
        
        $user->save();
        
        return redirect()->route('profile.edit')->with('status', 'Perfil atualizado com sucesso!');
    }

    public function backup()
    {
        try {
            // Cria o diretório se não existir
            $backupPath = storage_path('app/backups');
            if (!File::exists($backupPath)) {
                File::makeDirectory($backupPath, 0775, true);
            }

            // Debug: Listar todos os arquivos no diretório
            $allFiles = File::files($backupPath);
            \Log::info('Arquivos encontrados:', ['files' => $allFiles]);

            // Lista todos os arquivos .sql e .zip no diretório backups
            $backups = collect(File::files($backupPath))
                ->filter(function($file) {
                    $extension = $file->getExtension();
                    return in_array($extension, ['sql', 'zip']);
                })
                ->map(function ($file) {
                    return (object) [
                        'name' => $file->getFilename(),
                        'size' => $this->formatBytes($file->getSize()),
                        'date' => Carbon::createFromTimestamp($file->getMTime())->format('d/m/Y H:i:s'),
                        'path' => $file->getPathname(),
                        'type' => $file->getExtension(),
                        'icon' => $file->getExtension() === 'zip' ? 'ri-file-zip-line' : 'ri-database-2-line'
                    ];
                })
                ->sortByDesc('date')
                ->values();

            \Log::info('Backups encontrados:', ['backups' => $backups]);

            return view('settings.backup.index', compact('backups'));
        } catch (\Exception $e) {
            \Log::error('Erro ao listar backups', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Erro ao listar backups: ' . $e->getMessage());
        }
    }

    public function createBackup()
    {
        try {
            // Cria o diretório de backup se não existir
            $backupPath = storage_path('app/backups');
            if (!File::exists($backupPath)) {
                File::makeDirectory($backupPath, 0775, true);
                shell_exec('chown www-data:www-data ' . $backupPath);
            }

            // Nome dos arquivos
            $timestamp = date('Y-m-d_H-i-s');
            $sqlFile = 'database_' . $timestamp . '.sql';
            $zipFile = 'backup_' . $timestamp . '.zip';
            
            $sqlPath = $backupPath . '/' . $sqlFile;
            $zipPath = $backupPath . '/' . $zipFile;

            // 1. Backup do Banco de Dados
            $command = sprintf(
                'MYSQL_PWD="%s" mysqldump -u%s %s > %s 2>&1',
                config('database.connections.mysql.password'),
                config('database.connections.mysql.username'),
                config('database.connections.mysql.database'),
                $sqlPath
            );

            exec($command, $output, $resultCode);

            if ($resultCode !== 0) {
                throw new \Exception('Erro ao executar mysqldump: ' . implode("\n", $output));
            }

            // 2. Criar arquivo ZIP
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Não foi possível criar o arquivo ZIP');
            }

            // Adicionar SQL ao ZIP
            $zip->addFile($sqlPath, 'database/' . $sqlFile);

            // Adicionar diretórios importantes
            $directories = [
                'app' => 'Arquivos da Aplicação',
                'config' => 'Arquivos de Configuração',
                'database' => 'Migrações e Seeds',
                'public' => 'Arquivos Públicos',
                'resources' => 'Views e Assets',
                'routes' => 'Rotas',
                'storage/app/public' => 'Arquivos Enviados'
            ];

            foreach ($directories as $dir => $description) {
                $this->addDirectoryToZip($zip, base_path($dir), $dir);
            }

            // Adicionar arquivos importantes
            $files = [
                '.env' => 'Configurações do Ambiente',
                'composer.json' => 'Dependências do Composer',
                'composer.lock' => 'Versões das Dependências',
                'package.json' => 'Dependências NPM',
                'webpack.mix.js' => 'Configuração do Mix'
            ];

            foreach ($files as $file => $description) {
                if (File::exists(base_path($file))) {
                    $zip->addFile(base_path($file), $file);
                }
            }

            // Adicionar arquivo de manifesto
            $manifest = "Backup criado em: " . date('d/m/Y H:i:s') . "\n\n";
            $manifest .= "Conteúdo do Backup:\n\n";
            $manifest .= "1. Banco de Dados\n";
            $manifest .= "   - {$sqlFile}\n\n";
            $manifest .= "2. Diretórios:\n";
            foreach ($directories as $dir => $description) {
                $manifest .= "   - {$dir}: {$description}\n";
            }
            $manifest .= "\n3. Arquivos:\n";
            foreach ($files as $file => $description) {
                if (File::exists(base_path($file))) {
                    $manifest .= "   - {$file}: {$description}\n";
                }
            }

            $zip->addFromString('manifest.txt', $manifest);
            $zip->close();

            // Remover arquivo SQL temporário
            File::delete($sqlPath);

            // Ajustar permissões do ZIP
            chmod($zipPath, 0664);
            shell_exec('chown www-data:www-data ' . $zipPath);

            return redirect()->route('settings.backup')
                ->with('success', 'Backup completo criado com sucesso: ' . $zipFile);

        } catch (\Exception $e) {
            \Log::error('Erro no backup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('settings.backup')
                ->with('error', 'Erro ao criar backup: ' . $e->getMessage());
        }
    }

    private function addDirectoryToZip($zip, $path, $zipPath)
    {
        if (!is_dir($path)) return;

        // Criar o diretório no ZIP
        $zip->addEmptyDir($zipPath);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(base_path()) + 1);

                // Ignorar alguns tipos de arquivos
                if ($this->shouldIgnoreFile($relativePath)) {
                    continue;
                }

                $zip->addFile($filePath, $zipPath . '/' . basename($filePath));
            }
        }
    }

    private function shouldIgnoreFile($path)
    {
        $ignoredPatterns = [
            '/vendor/',
            '/node_modules/',
            '/.git/',
            '/.idea/',
            '/.vscode/',
            '/.env',
            '/.DS_Store',
            '/storage/logs/',
            '/storage/framework/cache/',
            '/storage/framework/sessions/',
            '/storage/framework/views/',
            '/.phpunit.cache/',
            '/bootstrap/cache/',
        ];

        foreach ($ignoredPatterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    public function downloadBackup($filename)
    {
        try {
            $backupPath = storage_path('app/backups/' . $filename);

            \Log::info('Tentando download', [
                'filename' => $filename,
                'path' => $backupPath,
                'exists' => File::exists($backupPath),
                'size' => File::exists($backupPath) ? File::size($backupPath) : 0,
                'perms' => File::exists($backupPath) ? substr(sprintf('%o', fileperms($backupPath)), -4) : null
            ]);

            if (!File::exists($backupPath)) {
                throw new \Exception('Arquivo de backup não encontrado: ' . $backupPath);
            }

            if (!is_readable($backupPath)) {
                throw new \Exception('Arquivo de backup não pode ser lido: ' . $backupPath);
            }

            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $contentType = $extension === 'zip' ? 'application/zip' : 'application/sql';

            return response()->download($backupPath, $filename, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao baixar backup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('settings.backup')
                ->with('error', 'Erro ao baixar backup: ' . $e->getMessage());
        }
    }

    public function deleteBackup($filename)
    {
        try {
            $backupPath = storage_path('app/backups/' . $filename);

            if (!File::exists($backupPath)) {
                return redirect()->route('settings.backup')
                    ->with('error', 'Arquivo não encontrado.');
            }

            File::delete($backupPath);
            return redirect()->route('settings.backup')
                ->with('success', 'Backup excluído com sucesso!');

        } catch (\Exception $e) {
            \Log::error('Erro ao excluir backup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('settings.backup')
                ->with('error', 'Erro ao excluir backup: ' . $e->getMessage());
        }
    }

    public function restoreBackup(Request $request)
    {
        try {
            $request->validate([
                'backup_file' => 'required|file|mimes:zip'
            ]);

            $file = $request->file('backup_file');
            $tempPath = storage_path('app/temp/restore_' . time());
            
            // Criar diretório temporário
            if (!File::exists($tempPath)) {
                File::makeDirectory($tempPath, 0755, true);
            }

            // Extrair ZIP
            $zip = new ZipArchive();
            if ($zip->open($file->getRealPath()) !== true) {
                throw new \Exception('Não foi possível abrir o arquivo ZIP');
            }

            $zip->extractTo($tempPath);
            $zip->close();

            // Restaurar banco de dados
            $sqlFile = File::glob($tempPath . '/database/*.sql')[0] ?? null;
            if ($sqlFile) {
                $command = sprintf(
                    'MYSQL_PWD="%s" mysql -u%s %s < %s',
                    config('database.connections.mysql.password'),
                    config('database.connections.mysql.username'),
                    config('database.connections.mysql.database'),
                    $sqlFile
                );

                exec($command, $output, $resultCode);
                if ($resultCode !== 0) {
                    throw new \Exception('Erro ao restaurar banco de dados');
                }
            }

            // Restaurar arquivos
            $this->restoreFiles($tempPath);

            // Limpar diretório temporário
            File::deleteDirectory($tempPath);

            // Limpar caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');

            return redirect()->route('settings.backup')
                ->with('success', 'Backup restaurado com sucesso!');

        } catch (\Exception $e) {
            \Log::error('Erro na restauração', ['error' => $e->getMessage()]);
            return redirect()->route('settings.backup')
                ->with('error', 'Erro ao restaurar backup: ' . $e->getMessage());
        }
    }

    private function restoreFiles($tempPath)
    {
        // Restaurar diretórios principais
        $directories = ['app', 'config', 'database', 'resources', 'routes'];
        foreach ($directories as $dir) {
            if (File::exists($tempPath . '/' . $dir)) {
                File::copyDirectory($tempPath . '/' . $dir, base_path($dir));
            }
        }

        // Restaurar arquivos públicos
        if (File::exists($tempPath . '/public')) {
            File::copyDirectory($tempPath . '/public', public_path());
        }

        // Restaurar storage
        if (File::exists($tempPath . '/storage')) {
            File::copyDirectory($tempPath . '/storage', storage_path('app/public'));
        }

        // Restaurar arquivos de configuração
        if (File::exists($tempPath . '/env/.env')) {
            File::copy($tempPath . '/env/.env', base_path('.env'));
        }
        if (File::exists($tempPath . '/composer.json')) {
            File::copy($tempPath . '/composer.json', base_path('composer.json'));
        }
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function generateTransactionsReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $transactions = Transaction::with(['category', 'account'])
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->orderBy('date')
            ->get();

        // Gera o CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="transacoes.csv"',
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Cabeçalho do CSV
            fputcsv($file, ['Data', 'Descrição', 'Categoria', 'Conta', 'Tipo', 'Valor']);
            
            // Dados
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->date->format('d/m/Y'),
                    $transaction->description,
                    $transaction->category->name,
                    $transaction->account->name,
                    $transaction->type === 'income' ? 'Receita' : 'Despesa',
                    number_format($transaction->amount / 100, 2, ',', '.'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Retorna a lista de usuários para a funcionalidade de exclusão de dados (Admin).
     * Exclui o próprio usuário logado.
     */
    private function usersForDeletion()
    {
        return User::where('id', '!=', auth()->id())->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Exclui todos os dados financeiros de um usuário selecionado (Admin).
     */
    public function deleteUserData(Request $request)
    {
        // 1. Verificação de Admin (Dupla checagem)
        if (!auth()->user()->is_admin) {
            return back()->with('error', 'Acesso negado. Apenas administradores podem executar esta ação.');
        }

        // 2. Validação
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $userIdToDelete = $request->input('user_id');
        $targetUser = User::find($userIdToDelete);
        
        if (!$targetUser) {
             return back()->with('error', 'Usuário não encontrado.');
        }

        // 4. Lógica de Exclusão com Transação
        try {
            DB::transaction(function () use ($userIdToDelete) {
                // Excluir Transações
                Transaction::where('user_id', $userIdToDelete)->delete();
                
                // Não excluir Contas - Linha removida/comentada
                // Account::where('user_id', $userIdToDelete)->delete(); 
                
                // Excluir Categorias
                Category::where('user_id', $userIdToDelete)->delete();

                // Adicionar aqui outros modelos se necessário (ex: orçamentos, metas, etc.)
                // Ex: Budget::where('user_id', $userIdToDelete)->delete();

            });

            // 5. Feedback
            // Mensagem personalizada se for auto-exclusão
            $message = ($userIdToDelete == auth()->id())
                ? 'Seus dados financeiros foram excluídos com sucesso.'
                : 'Todos os dados financeiros do usuário ' . $targetUser->name . ' foram excluídos com sucesso.';
                
            return back()->with('success', $message);

        } catch (\Exception $e) {
            // Logar o erro real para debug
             \Log::error('Erro ao excluir dados financeiros do usuário: ' . $e->getMessage(), [
                'user_id_to_delete' => $userIdToDelete,
                'admin_user_id' => auth()->id()
             ]);
             
            // 6. Feedback de Erro
             return back()->with('error', 'Ocorreu um erro ao tentar excluir os dados do usuário. Verifique os logs.');
        }
    }

    /**
     * Exibe sistema de atualização (verifica versão local x remoto)
     *
     * ATENÇÃO: Lógica crítica de verificação de versão; localVersion fixada em v1.0.0.
     * NÃO ALTERAR ESSA FUNÇÃO SEM AUTORIZAÇÃO EXPLÍCITA.
     */
    public function system()
    {
        // Define local version explicitly
        $localVersion = 'v1.0.0';
        $remoteVersion = 'error';
        // Busca todas as tags e define a mais recente por versão sem depender de releases
        try {
            $tagsResp = Http::withHeaders(['Accept' => 'application/vnd.github.v3+json'])
                          ->get('https://api.github.com/repos/onlitec/onlifin/tags');
            $tags = $tagsResp->json();
            $versions = array_column($tags, 'name');
            usort($versions, function($a, $b) {
                return version_compare(ltrim($b, 'vV'), ltrim($a, 'vV'));
            });
            $remoteVersion = $versions[0] ?? 'no-remote-tag';
        } catch (\Exception $e) {
            $remoteVersion = 'error';
        }
        $isUpToDate = version_compare($localVersion, $remoteVersion, '>=');
        return view('settings.system.index', compact('localVersion', 'remoteVersion', 'isUpToDate'));
    }

    /**
     * Aparência: exibe opções para configurar título e favicon do site.
     *
     * IMPLEMENTAÇÃO CRÍTICA: Esta funcionalidade está disponível em http://onlifin.onlitec.com.br/settings/appearance
     * NÃO MODIFICAR sem autorização explícita.
     */
    public function appearance()
    {
        $siteTitle = Setting::get('site_title', config('app.name'));
        $siteFavicon = Setting::get('site_favicon', 'favicon.ico');
        $siteTheme = Setting::get('site_theme', 'light');
        $rootFontSize = Setting::get('root_font_size', '16');
        $siteLogo = Setting::get('site_logo', null);
        $cardFontSize = Setting::get('card_font_size', '2xl');
        return view('settings.appearance', compact('siteTitle', 'siteFavicon', 'siteTheme', 'siteLogo', 'rootFontSize', 'cardFontSize'));
    }

    /**
     * Atualiza configurações de aparência (título e favicon).
     *
     * IMPLEMENTAÇÃO CRÍTICA: Esta funcionalidade está disponível em http://onlifin.onlitec.com.br/settings/appearance
     * NÃO MODIFICAR sem autorização explícita.
     */
    public function updateAppearance(Request $request)
    {
        $data = $request->validate([
            'site_title' => 'required|string|max:255',
            'site_logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:5120',
            'site_favicon' => 'nullable|image|mimes:png,ico,svg|max:1024',
            'site_theme' => 'required|in:light,dark',
            'root_font_size'  => 'required|in:14,16,18,20',
            'card_font_size'  => 'required|in:lg,xl,2xl,3xl',
        ]);
        
        Setting::set('site_title', $data['site_title']);
        Setting::set('site_theme', $data['site_theme']);
        Setting::set('root_font_size', $data['root_font_size']);
        Setting::set('card_font_size', $data['card_font_size']);
        
        if ($request->hasFile('site_logo')) {
            $logoFile = $request->file('site_logo');
            $extension = $logoFile->getClientOriginalExtension();
            
            // Se for PNG, salvamos diretamente para preservar transparência
            if (strtolower($extension) === 'png') {
                $fileName = 'logo-' . time() . '.png';
                $pathLogo = $logoFile->storeAs('site-logos', $fileName, 'public');
            } else {
                // Para outros formatos, usamos o método padrão
                $pathLogo = $logoFile->store('site-logos', 'public');
            }
            
            Setting::set('site_logo', 'storage/' . $pathLogo);
            
            // Verificar e corrigir permissões do arquivo
            $fullPath = storage_path('app/public/' . $pathLogo);
            if (file_exists($fullPath)) {
                chmod($fullPath, 0644); // Garantir permissões de leitura
            }
        }
        
        if ($request->hasFile('site_favicon')) {
            $path = $request->file('site_favicon')->store('favicons', 'public');
            Setting::set('site_favicon', 'storage/' . $path);
        }
        
        return redirect()->route('settings.appearance')->with('success', 'Configurações de aparência salvas com sucesso!');
    }

    /**
     * Faz backup da plataforma (zip do projeto)
     */
    public function backupPlatform()
    {
        $ts = now()->format('YmdHis');
        $file = storage_path("app/backups/platform_{$ts}.zip");
        $zip = new \ZipArchive();
        if ($zip->open($file, ZipArchive::CREATE|ZipArchive::OVERWRITE) === true) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path()));
            foreach ($it as $f) {
                if ($f->isDir()) continue;
                if (strpos($f->getPathname(), storage_path('app/backups')) === 0) continue;
                $zip->addFile($f->getPathname(), substr($f->getPathname(), strlen(base_path())+1));
            }
            $zip->close();
        }
        return response()->download($file)->deleteFileAfterSend(true);
    }

    /**
     * Atualiza plataforma: git pull, composer, migrate
     */
    public function updatePlatform()
    {
        // Backup pré-atualização usando o sistema existente
        $this->createBackup();
        // Determinar caminhos completos dos binários
        $git = trim(shell_exec('which git'));
        $composer = trim(shell_exec('which composer'));
        $php = PHP_BINARY;
        $commands = [
            // Adicionar diretório ao safe.directory globalmente para o usuário do processo
            "$git config --global --add safe.directory " . base_path() . " 2>&1",
            // Atualizar código
            "$git pull origin main 2>&1",
            "$composer install --no-interaction 2>&1",
            "$php artisan migrate --force 2>&1"
        ];
        foreach ($commands as $cmd) {
            exec("cd " . base_path() . " && $cmd", $out, $st);
            if ($st !== 0) {
                $output = implode("\n", $out);
                return redirect()->route('settings.system')
                    ->with('error', "Erro ao executar: $cmd. Saída: $output");
            }
        }
        return redirect()->route('settings.system')->with('success', 'Atualizado com sucesso!');
    }

    /**
     * Gera e exibe relatório financeiro detalhado com insights e explicações.
     */
    public function financialReport(FinancialReportAIService $aiService, Request $request, FinancialDataService $financialDataService)
    {
        $user = auth()->user();
        $periodo = $request->input('periodo', 'mensal');
        
        // Obtém transações do mês atual
        $transactions = Transaction::when(!$user->isAdmin(), function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->select('description', 'amount', 'category_id', 'date', 'type')
            ->with('category:id,name')
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->get()
            ->map(function($t) {
                return [
                    'descricao' => $t->description,
                    'valor' => $t->amount / 100,
                    'categoria' => $t->category ? $t->category->name : null,
                    'data' => $t->date->format('Y-m-d'),
                    'tipo' => $t->type, // Adicionando o tipo (income/expense)
                ];
            })->toArray();
        
        // Obtém dados financeiros adicionais
        $dadosAdicionais = [
            'contas_bancarias' => $financialDataService->getBankAccountsBalance()->toArray(),
            'resumo_financeiro' => $financialDataService->getFinancialSummary(),
            'transacoes_recentes' => $financialDataService->getRecentTransactions(10)->toArray(),
        ];
        
        // Combina os dados para enviar à IA
        $dadosCompletos = [
            'transacoes' => $transactions,
            'dados_adicionais' => $dadosAdicionais,
        ];
        
        $report = $aiService->generateReport($dadosCompletos, $periodo);
        return view('settings.reports.financial', compact('report', 'periodo'));
    }

    public function exportIncomesByAccount(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $user = auth()->user();
        $userId = $user->id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $incomesByAccount = Transaction::with(['account'])
            ->when(!$user->isAdmin(), function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('type', 'income')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        // Gera o CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="receitas_por_conta.csv"',
        ];

        $callback = function() use ($incomesByAccount) {
            $file = fopen('php://output', 'w');
            
            // Cabeçalho do CSV
            fputcsv($file, ['Data', 'Descrição', 'Conta', 'Valor']);
            
            // Dados
            foreach ($incomesByAccount as $income) {
                fputcsv($file, [
                    $income->date->format('d/m/Y'),
                    $income->description,
                    $income->account->name ?? 'Sem conta',
                    number_format($income->amount / 100, 2, ',', '.'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function exportExpensesByAccount(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $user = auth()->user();
        $userId = $user->id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $expensesByAccount = Transaction::with(['account'])
            ->when(!$user->isAdmin(), function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        // Gera o CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="despesas_por_conta.csv"',
        ];

        $callback = function() use ($expensesByAccount) {
            $file = fopen('php://output', 'w');
            
            // Cabeçalho do CSV
            fputcsv($file, ['Data', 'Descrição', 'Conta', 'Valor']);
            
            // Dados
            foreach ($expensesByAccount as $expense) {
                fputcsv($file, [
                    $expense->date->format('d/m/Y'),
                    $expense->description,
                    $expense->account->name ?? 'Sem conta',
                    number_format($expense->amount / 100, 2, ',', '.'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}