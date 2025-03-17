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
use Twilio\Rest\Client;

class SettingsController extends Controller
{
    use WithPagination;

    public function index()
    {
        $isAdmin = auth()->user()->is_admin ?? false;
        \Illuminate\Support\Facades\Log::info('Acessando configurações', [
            'user_id' => auth()->id(),
            'is_admin' => $isAdmin,
            'email' => auth()->user()->email,
            'request_path' => request()->path(),
            'request_url' => request()->url()
        ]);
        return view('settings.index', compact('isAdmin'));
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
        return view('settings.permissions.index', compact('permissions'));
    }

    public function reports()
    {
        $accounts = \App\Models\Account::all();
        return view('settings.reports.index', compact('accounts'));
    }

    /**
     * Gera relatório de gastos por categoria
     */
    public function expensesByCategory(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'sort_by' => 'required|in:amount,name,count'
        ]);

        $query = Transaction::with('category')
            ->where('type', 'expense')
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->select('category_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('category_id');

        // Ordenação
        switch ($request->sort_by) {
            case 'amount':
                $query->orderByDesc('total_amount');
                break;
            case 'name':
                $query->join('categories', 'transactions.category_id', '=', 'categories.id')
                    ->orderBy('categories.name');
                break;
            case 'count':
                $query->orderByDesc('count');
                break;
        }

        $results = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="gastos_por_categoria.csv"',
        ];

        $callback = function() use ($results) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Categoria', 'Quantidade', 'Valor Total']);
            
            foreach ($results as $result) {
                fputcsv($file, [
                    $result->category->name,
                    $result->count,
                    number_format($result->total_amount / 100, 2, ',', '.'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Gera relatório de receitas por categoria
     */
    public function incomeByCategory(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'sort_by' => 'required|in:amount,name,count'
        ]);

        $query = Transaction::with('category')
            ->where('type', 'income')
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->select('category_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('category_id');

        // Ordenação
        switch ($request->sort_by) {
            case 'amount':
                $query->orderByDesc('total_amount');
                break;
            case 'name':
                $query->join('categories', 'transactions.category_id', '=', 'categories.id')
                    ->orderBy('categories.name');
                break;
            case 'count':
                $query->orderByDesc('count');
                break;
        }

        $results = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="receitas_por_categoria.csv"',
        ];

        $callback = function() use ($results) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Categoria', 'Quantidade', 'Valor Total']);
            
            foreach ($results as $result) {
                fputcsv($file, [
                    $result->category->name,
                    $result->count,
                    number_format($result->total_amount / 100, 2, ',', '.'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Gera relatório de fluxo de caixa
     */
    public function cashFlow(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'required|in:day,week,month'
        ]);

        $query = Transaction::select(
            DB::raw('DATE(date) as date'),
            DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income'),
            DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expense')
        )
        ->whereBetween('date', [$request->start_date, $request->end_date])
        ->groupBy('date');

        // Agrupamento
        switch ($request->group_by) {
            case 'week':
                $query->select(
                    DB::raw('YEARWEEK(date) as period'),
                    DB::raw('MIN(date) as date'),
                    DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income'),
                    DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expense')
                )
                ->groupBy('period');
                break;
            case 'month':
                $query->select(
                    DB::raw('DATE_FORMAT(date, "%Y-%m") as period'),
                    DB::raw('MIN(date) as date'),
                    DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income'),
                    DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expense')
                )
                ->groupBy('period');
                break;
        }

        $results = $query->orderBy('date')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="fluxo_caixa.csv"',
        ];

        $callback = function() use ($results) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Data', 'Receitas', 'Despesas', 'Saldo']);
            
            foreach ($results as $result) {
                $balance = ($result->income - $result->expense) / 100;
                fputcsv($file, [
                    $result->date,
                    number_format($result->income / 100, 2, ',', '.'),
                    number_format($result->expense / 100, 2, ',', '.'),
                    number_format($balance, 2, ',', '.'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Gera relatório de análise comparativa
     */
    public function comparativeAnalysis(Request $request)
    {
        $request->validate([
            'start_date_1' => 'required|date',
            'end_date_1' => 'required|date|after_or_equal:start_date_1',
            'start_date_2' => 'required|date',
            'end_date_2' => 'required|date|after_or_equal:start_date_2',
        ]);

        // Período 1
        $period1 = Transaction::select(
            DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income'),
            DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expense')
        )
        ->whereBetween('date', [$request->start_date_1, $request->end_date_1])
        ->first();

        // Período 2
        $period2 = Transaction::select(
            DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income'),
            DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expense')
        )
        ->whereBetween('date', [$request->start_date_2, $request->end_date_2])
        ->first();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="analise_comparativa.csv"',
        ];

        $callback = function() use ($period1, $period2, $request) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Período', 'Receitas', 'Despesas', 'Saldo', 'Variação']);
            
            // Período 1
            $balance1 = ($period1->income - $period1->expense) / 100;
            fputcsv($file, [
                $request->start_date_1 . ' a ' . $request->end_date_1,
                number_format($period1->income / 100, 2, ',', '.'),
                number_format($period1->expense / 100, 2, ',', '.'),
                number_format($balance1, 2, ',', '.'),
                '0%'
            ]);

            // Período 2
            $balance2 = ($period2->income - $period2->expense) / 100;
            $variation = $balance1 != 0 ? (($balance2 - $balance1) / abs($balance1)) * 100 : 0;
            fputcsv($file, [
                $request->start_date_2 . ' a ' . $request->end_date_2,
                number_format($period2->income / 100, 2, ',', '.'),
                number_format($period2->expense / 100, 2, ',', '.'),
                number_format($balance2, 2, ',', '.'),
                number_format($variation, 2, ',', '.') . '%'
            ]);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Gera relatório de projeção financeira
     */
    public function financialProjection(Request $request)
    {
        $request->validate([
            'months' => 'required|integer|min:1|max:12',
            'include_fixed' => 'boolean',
            'include_recurring' => 'boolean',
            'include_installments' => 'boolean'
        ]);

        $projection = [];
        $currentDate = now();
        $endDate = $currentDate->copy()->addMonths($request->months);

        // Buscar transações recorrentes
        $recurringTransactions = Transaction::where(function($query) {
            $query->where('transaction_type', 'recurring')
                ->orWhere('transaction_type', 'fixed')
                ->orWhere('transaction_type', 'installment');
        })
        ->where('date', '<=', $endDate)
        ->get();

        // Gerar projeção mês a mês
        while ($currentDate <= $endDate) {
            $monthStart = $currentDate->copy()->startOfMonth();
            $monthEnd = $currentDate->copy()->endOfMonth();
            
            $monthlyIncome = 0;
            $monthlyExpense = 0;

            foreach ($recurringTransactions as $transaction) {
                if ($this->shouldIncludeTransaction($transaction, $monthStart, $monthEnd, $request)) {
                    if ($transaction->type === 'income') {
                        $monthlyIncome += $transaction->amount;
                    } else {
                        $monthlyExpense += $transaction->amount;
                    }
                }
            }

            $projection[] = [
                'month' => $currentDate->format('Y-m'),
                'income' => $monthlyIncome,
                'expense' => $monthlyExpense,
                'balance' => $monthlyIncome - $monthlyExpense
            ];

            $currentDate->addMonth();
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="projecao_financeira.csv"',
        ];

        $callback = function() use ($projection) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Mês', 'Receitas', 'Despesas', 'Saldo']);
            
            foreach ($projection as $month) {
                fputcsv($file, [
                    $month['month'],
                    number_format($month['income'] / 100, 2, ',', '.'),
                    number_format($month['expense'] / 100, 2, ',', '.'),
                    number_format($month['balance'] / 100, 2, ',', '.')
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Verifica se uma transação deve ser incluída na projeção
     */
    private function shouldIncludeTransaction($transaction, $monthStart, $monthEnd, $request)
    {
        if ($transaction->transaction_type === 'fixed' && !$request->include_fixed) {
            return false;
        }

        if ($transaction->transaction_type === 'recurring' && !$request->include_recurring) {
            return false;
        }

        if ($transaction->transaction_type === 'installment' && !$request->include_installments) {
            return false;
        }

        // Verificar se a transação cai dentro do mês
        $transactionDate = Carbon::parse($transaction->date);
        return $transactionDate->between($monthStart, $monthEnd);
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
            'phone' => 'nullable|string|max:20',
        ]);
        
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        
        // Atualizar preferências de notificação
        $user->notifications_email = $request->has('notifications_email');
        $user->notifications_whatsapp = $request->has('notifications_whatsapp');
        
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
     * Exibe a página de configurações de notificações
     */
    public function notifications()
    {
        return view('settings.notifications.index');
    }

    /**
     * Atualiza as configurações de e-mail
     */
    public function updateEmailSettings(Request $request)
    {
        $request->validate([
            'mail_mailer' => 'required|string',
            'mail_host' => 'required|string',
            'mail_port' => 'required|string',
            'mail_from_name' => 'required|string',
            'mail_from_address' => 'required|email',
            'mail_username' => 'required|string',
            'mail_encryption' => 'nullable|string',
        ]);

        try {
            // Atualiza o arquivo .env com as novas configurações
            $this->updateEnvValue('MAIL_MAILER', $request->mail_mailer);
            $this->updateEnvValue('MAIL_HOST', $request->mail_host);
            $this->updateEnvValue('MAIL_PORT', $request->mail_port);
            $this->updateEnvValue('MAIL_FROM_NAME', '"'.$request->mail_from_name.'"');
            $this->updateEnvValue('MAIL_FROM_ADDRESS', $request->mail_from_address);
            $this->updateEnvValue('MAIL_USERNAME', $request->mail_username);
            $this->updateEnvValue('MAIL_ENCRYPTION', $request->mail_encryption ?? 'null');
            
            // Atualiza a senha apenas se fornecida
            if ($request->filled('mail_password')) {
                $this->updateEnvValue('MAIL_PASSWORD', $request->mail_password);
            }

            // Limpa o cache de configuração
            Artisan::call('config:clear');
            
            return redirect()->route('settings.notifications')->with('success', 'Configurações de e-mail atualizadas com sucesso!');
        } catch (\Exception $e) {
            \Log::error('Erro ao atualizar configurações de e-mail: ' . $e->getMessage());
            return redirect()->route('settings.notifications')->with('error', 'Erro ao atualizar configurações: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza as configurações do WhatsApp (Twilio)
     */
    public function updateWhatsappSettings(Request $request)
    {
        \Log::info('=== INÍCIO ATUALIZAÇÃO CONFIGURAÇÕES WHATSAPP ===');
        \Log::info('Dados recebidos:', ['has_enabled' => $request->has('twilio_enabled'), 'has_whatsapp' => $request->has('twilio_whatsapp')]);
        
        try {
            $enabled = $request->has('twilio_enabled') ? 'true' : 'false';
            $whatsapp = $request->has('twilio_whatsapp') ? 'true' : 'false';
            
            // Atualiza o arquivo .env com as novas configurações
            $this->updateEnvValue('TWILIO_ENABLED', $enabled);
            
            // Apenas atualiza o Account SID se foi fornecido
            if ($request->filled('twilio_account_sid')) {
                $this->updateEnvValue('TWILIO_ACCOUNT_SID', $request->input('twilio_account_sid'));
                \Log::info('Account SID atualizado');
            }
            
            // Apenas atualiza o Auth Token se foi fornecido
            if ($request->filled('twilio_auth_token')) {
                $this->updateEnvValue('TWILIO_AUTH_TOKEN', $request->input('twilio_auth_token'));
                \Log::info('Auth Token atualizado');
            }
            
            // Atualiza o número FROM se foi fornecido
            if ($request->filled('twilio_from')) {
                $from = $request->input('twilio_from');
                // Adiciona o prefixo whatsapp: se não estiver presente e se whatsapp estiver habilitado
                if ($whatsapp == 'true' && !str_starts_with($from, 'whatsapp:')) {
                    $from = 'whatsapp:' . $from;
                }
                $this->updateEnvValue('TWILIO_FROM', $from);
                \Log::info('FROM atualizado');
            }
            
            $this->updateEnvValue('TWILIO_WHATSAPP', $whatsapp);
            $this->updateEnvValue('TWILIO_SANDBOX', 'true'); // Mantém sandbox ativado por segurança

            // Limpa o cache de configuração
            Artisan::call('config:clear');
            
            return redirect()->route('settings.notifications')->with('success', 'Configurações de WhatsApp atualizadas com sucesso!');
        } catch (\Exception $e) {
            \Log::error('Erro ao atualizar configurações de WhatsApp: ' . $e->getMessage());
            return redirect()->route('settings.notifications')->with('error', 'Erro ao atualizar configurações: ' . $e->getMessage());
        }
    }

    /**
     * Testa o envio de e-mail
     */
    public function testEmail()
    {
        try {
            \Log::info('Iniciando teste de e-mail', [
                'user_email' => auth()->user()->email,
                'mail_config' => [
                    'mailer' => config('mail.mailer'),
                    'host' => config('mail.host'),
                    'port' => config('mail.port'),
                    'from_address' => config('mail.from.address'),
                    'encryption' => config('mail.encryption')
                ]
            ]);

            $user = auth()->user();
            
            \Mail::raw('Este é um e-mail de teste do sistema Onlifin. Se você recebeu esta mensagem, as configurações de e-mail estão corretas.', function($message) use ($user) {
                $message->to($user->email)
                        ->subject('Teste de Configuração de E-mail - Onlifin');
            });
            
            \Log::info('E-mail de teste enviado com sucesso', ['user_email' => $user->email]);
            
            return response()->json([
                'success' => true,
                'message' => 'E-mail enviado com sucesso'
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao enviar e-mail de teste', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_email' => auth()->user()->email
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar e-mail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Testa o envio de mensagem via WhatsApp
     */
    public function testWhatsapp(Request $request)
    {
        try {
            // Log da entrada na função
            \Log::info('=== INÍCIO DO TESTE DE WHATSAPP ===');
            \Log::info('Método testWhatsapp chamado com sucesso', [
                'request_method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'accept' => $request->header('Accept'),
                'raw_content' => $request->getContent(),
                'post_data' => $_POST,
                'query_string' => $_SERVER['QUERY_STRING'] ?? '',
                'all_params' => $request->all(),
                'input_json' => json_decode($request->getContent(), true),
                'phone' => $request->phone,
                'twilio_enabled' => config('services.twilio.enabled'),
                'twilio_account_sid' => config('services.twilio.account_sid'),
                'twilio_from' => config('services.twilio.from')
            ]);

            // Se o número de telefone não estiver definido, retornar erro
            if (!$request->has('phone') || empty($request->phone)) {
                \Log::warning('Tentativa de teste do WhatsApp sem número de telefone');
                return response()->json([
                    'success' => false, 
                    'message' => 'O número de telefone é obrigatório.'
                ], 400);
            }

            // Verificar se o WhatsApp está habilitado
            if (!config('services.twilio.enabled', false)) {
                \Log::warning('Tentativa de teste do WhatsApp com serviço desabilitado');
                return response()->json([
                    'success' => false, 
                    'message' => 'O WhatsApp não está habilitado nas configurações.'
                ], 400);
            }

            $phone = $request->phone;
            
            // Formatar o número para o padrão do Twilio
            if (!str_starts_with($phone, '+')) {
                $phone = '+' . $phone;
            }
            
            // Adicionar prefixo whatsapp: se necessário
            if (!str_starts_with($phone, 'whatsapp:')) {
                $phone = 'whatsapp:' . $phone;
            }

            \Log::info('Número formatado para envio', ['formatted_phone' => $phone]);
            
            // Log das configurações do Twilio
            \Log::info('Configurações do Twilio', [
                'account_sid' => config('services.twilio.account_sid'),
                'auth_token' => '***MASKED***',
                'from' => config('services.twilio.from'),
                'sandbox' => config('services.twilio.sandbox', true)
            ]);
            
            $twilio = new Client(
                config('services.twilio.account_sid'),
                config('services.twilio.auth_token')
            );
            
            // Se estiver usando sandbox, adicionar o código de sandbox à mensagem
            $messageBody = 'Esta é uma mensagem de teste do sistema Onlifin. Se você recebeu esta mensagem, as configurações de WhatsApp estão corretas.';
            if (config('services.twilio.sandbox', true)) {
                $messageBody .= "\n\nCódigo de sandbox: " . config('services.twilio.from');
            }
            
            \Log::info('Tentando enviar mensagem', [
                'to' => $phone,
                'from' => config('services.twilio.from'),
                'message' => $messageBody
            ]);
            
            $message = $twilio->messages->create(
                $phone,
                [
                    'from' => config('services.twilio.from'),
                    'body' => $messageBody
                ]
            );
            
            \Log::info('Mensagem enviada com sucesso', [
                'message_sid' => $message->sid,
                'status' => $message->status,
                'date_created' => $message->dateCreated
            ]);
            
            \Log::info('=== FIM DO TESTE DE WHATSAPP ===');
            
            return response()->json([
                'success' => true,
                'message' => 'Mensagem enviada com sucesso',
                'sid' => $message->sid
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao enviar mensagem de WhatsApp', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'phone' => $request->phone ?? 'não informado'
            ]);
            
            \Log::info('=== FIM DO TESTE DE WHATSAPP COM ERRO ===');
            
            return response()->json([
                'success' => false, 
                'message' => 'Erro ao enviar mensagem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza um valor no arquivo .env
     */
    private function updateEnvValue($key, $value)
    {
        $path = base_path('.env');
        
        if (file_exists($path)) {
            $content = file_get_contents($path);
            
            // Se a chave já existe no arquivo
            if (strpos($content, "{$key}=") !== false) {
                $content = preg_replace("/{$key}=(.*)/", "{$key}={$value}", $content);
            } else {
                // Se a chave não existe, adicione-a
                $content .= "\n{$key}={$value}";
            }
            
            file_put_contents($path, $content);
        }
    }

    /**
     * Gera relatório de transações por conta
     */
    public function transactionsByAccount(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'account_id' => 'required|exists:accounts,id'
        ]);

        $query = Transaction::with(['category', 'account'])
            ->whereBetween('date', [$request->start_date, $request->end_date]);

        if ($request->account_id !== 'all') {
            $query->where('account_id', $request->account_id);
        }

        $transactions = $query->orderBy('date')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="transacoes_por_conta.csv"',
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Data', 'Conta', 'Categoria', 'Descrição', 'Tipo', 'Valor']);
            
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->date->format('d/m/Y'),
                    $transaction->account->name,
                    $transaction->category->name,
                    $transaction->description,
                    $transaction->type === 'income' ? 'Receita' : 'Despesa',
                    number_format($transaction->amount / 100, 2, ',', '.'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Gera relatório de pagamentos pendentes
     */
    public function pendingPayments(Request $request)
    {
        $request->validate([
            'due_period' => 'required|in:all,overdue,next7,next30,future',
            'transaction_type' => 'required|in:all,income,expense'
        ]);

        $query = Transaction::with(['category', 'account'])
            ->where('status', 'pending');

        // Filtrar por tipo de transação
        if ($request->transaction_type !== 'all') {
            $query->where('type', $request->transaction_type);
        }

        // Filtrar por período de vencimento
        switch ($request->due_period) {
            case 'overdue':
                $query->where('date', '<', now());
                break;
            case 'next7':
                $query->whereBetween('date', [now(), now()->addDays(7)]);
                break;
            case 'next30':
                $query->whereBetween('date', [now(), now()->addDays(30)]);
                break;
            case 'future':
                $query->where('date', '>', now()->addDays(30));
                break;
        }

        $transactions = $query->orderBy('date')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="pagamentos_pendentes.csv"',
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Data Vencimento', 'Conta', 'Categoria', 'Descrição', 'Tipo', 'Valor', 'Dias Atraso']);
            
            foreach ($transactions as $transaction) {
                $daysOverdue = $transaction->date->diffInDays(now(), false);
                fputcsv($file, [
                    $transaction->date->format('d/m/Y'),
                    $transaction->account->name,
                    $transaction->category->name,
                    $transaction->description,
                    $transaction->type === 'income' ? 'Receita' : 'Despesa',
                    number_format($transaction->amount / 100, 2, ',', '.'),
                    $daysOverdue > 0 ? $daysOverdue : 0
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Gera relatório de lucratividade
     */
    public function profitability(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'required|in:month,quarter,year'
        ]);

        $query = Transaction::select(
            DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income'),
            DB::raw('SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expense')
        )
        ->whereBetween('date', [$request->start_date, $request->end_date]);

        // Agrupamento
        switch ($request->group_by) {
            case 'month':
                $query->select(
                    DB::raw('DATE_FORMAT(date, "%Y-%m") as period'),
                    DB::raw('MIN(date) as date')
                )
                ->groupBy('period');
                break;
            case 'quarter':
                $query->select(
                    DB::raw('CONCAT(YEAR(date), "-Q", QUARTER(date)) as period'),
                    DB::raw('MIN(date) as date')
                )
                ->groupBy('period');
                break;
            case 'year':
                $query->select(
                    DB::raw('YEAR(date) as period'),
                    DB::raw('MIN(date) as date')
                )
                ->groupBy('period');
                break;
        }

        $results = $query->orderBy('date')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="lucratividade.csv"',
        ];

        $callback = function() use ($results) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Período', 'Receitas', 'Despesas', 'Lucro', 'Margem de Lucro']);
            
            foreach ($results as $result) {
                $income = $result->income / 100;
                $expense = $result->expense / 100;
                $profit = $income - $expense;
                $margin = $income > 0 ? ($profit / $income) * 100 : 0;

                fputcsv($file, [
                    $result->period,
                    number_format($income, 2, ',', '.'),
                    number_format($expense, 2, ',', '.'),
                    number_format($profit, 2, ',', '.'),
                    number_format($margin, 2, ',', '.') . '%'
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}