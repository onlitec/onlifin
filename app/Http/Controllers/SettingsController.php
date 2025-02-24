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

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    public function users()
    {
        $users = User::with('roles')->get();
        return view('settings.users.index', compact('users'));
    }

    public function roles()
    {
        $roles = Role::with('permissions')->get();
        return view('settings.roles.index', compact('roles'));
    }

    public function permissions()
    {
        $permissions = Permission::paginate(10);
        return view('settings.permissions.index', compact('permissions'));
    }

    public function reports()
    {
        return view('settings.reports.index');
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
} 