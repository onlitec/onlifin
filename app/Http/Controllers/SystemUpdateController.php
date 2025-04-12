<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Process\Process as SymfonyProcess;

class SystemUpdateController extends Controller
{
    public function index()
    {
        try {
            $updateInfo = $this->getUpdateInfo();
            return view('settings.system-update', compact('updateInfo'));
        } catch (\Exception $e) {
            Log::error('Erro ao obter informações de atualização: ' . $e->getMessage());
            return view('settings.system-update', ['error' => 'Erro ao obter informações do sistema.']);
        }
    }

    public function doUpdate(Request $request)
    {
        try {
            // Verifica se há alterações locais
            $updateInfo = $this->getUpdateInfo();
            if ($updateInfo['hasLocalChanges']) {
                return back()->with('error', 'Há alterações locais não commitadas que podem ser perdidas. Por favor, faça um backup ou commit das alterações antes de continuar.');
            }

            // Executa o backup se solicitado
            if ($request->has('backup') && $request->backup == '1') {
                $this->createBackup();
            }

            // Executa os comandos de atualização
            $this->runUpdateCommands();

            // Executa as migrações se solicitado
            if ($request->has('migrations') && $request->migrations == '1') {
                $this->runMigrations();
            }

            // Limpa os caches
            $this->clearCaches();

            return redirect()->route('settings.system-update')->with('success', 'Sistema atualizado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar sistema: ' . $e->getMessage());
            return back()->with('error', 'Erro ao atualizar sistema: ' . $e->getMessage());
        }
    }

    private function getUpdateInfo()
    {
        $process = new SymfonyProcess(['git', 'status', '--porcelain']);
        $process->run();
        $hasLocalChanges = $process->getOutput() !== '';

        $process = new SymfonyProcess(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);
        $process->run();
        $currentBranch = trim($process->getOutput());

        $process = new SymfonyProcess(['git', 'rev-parse', 'HEAD']);
        $process->run();
        $localHash = trim($process->getOutput());

        $process = new SymfonyProcess(['git', 'rev-parse', '@{u}']);
        $process->run();
        $remoteHash = trim($process->getOutput());

        $process = new SymfonyProcess(['git', 'log', '--pretty=format:%h %an %ad %s', '--date=short', '@{u}..HEAD']);
        $process->run();
        $aheadCount = substr_count($process->getOutput(), PHP_EOL);

        $process = new SymfonyProcess(['git', 'log', '--pretty=format:%h %an %ad %s', '--date=short', 'HEAD..@{u}']);
        $process->run();
        $behindCount = substr_count($process->getOutput(), PHP_EOL);

        $latestCommits = [];
        if ($behindCount > 0) {
            $process = new SymfonyProcess(['git', 'log', '--pretty=format:%h %an %ad %s', '--date=short', '-n', '5', '@{u}..HEAD']);
            $process->run();
            $commits = explode(PHP_EOL, trim($process->getOutput()));
            foreach ($commits as $commit) {
                list($hash, $author, $date, $message) = explode(' ', $commit, 4);
                $latestCommits[] = [
                    'hash' => $hash,
                    'author' => $author,
                    'date' => $date,
                    'message' => $message,
                ];
            }
        }

        return [
            'currentBranch' => $currentBranch,
            'localHash' => $localHash,
            'remoteHash' => $remoteHash,
            'hasLocalChanges' => $hasLocalChanges,
            'behindCount' => $behindCount,
            'aheadCount' => $aheadCount,
            'hasUpdates' => $behindCount > 0,
            'latestCommits' => $latestCommits,
        ];
    }

    private function createBackup()
    {
        $backupDir = storage_path('backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupFile = $backupDir . '/backup_' . $timestamp . '.sql';

        // Cria backup do banco de dados
        $process = new SymfonyProcess([
            'mysqldump',
            '--user=' . env('DB_USERNAME'),
            '--password=' . env('DB_PASSWORD'),
            '--host=' . env('DB_HOST'),
            env('DB_DATABASE'),
            '>',
            $backupFile
        ]);
        $process->run();

        if ($process->isSuccessful()) {
            Log::info('Backup criado com sucesso: ' . $backupFile);
        } else {
            Log::error('Erro ao criar backup: ' . $process->getErrorOutput());
            throw new \Exception('Erro ao criar backup do banco de dados.');
        }
    }

    private function runUpdateCommands()
    {
        $process = new SymfonyProcess(['git', 'pull', 'origin', 'main']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Erro ao atualizar o código: ' . $process->getErrorOutput());
        }

        // Atualiza as dependências
        $process = new SymfonyProcess(['composer', 'install', '--no-dev', '--optimize-autoloader']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Erro ao atualizar as dependências: ' . $process->getErrorOutput());
        }
    }

    private function runMigrations()
    {
        Artisan::call('migrate', [
            '--force' => true
        ]);

        if (Artisan::output() !== '') {
            Log::info('Migrações executadas com sucesso');
        } else {
            Log::error('Erro ao executar migrações: ' . Artisan::output());
            throw new \Exception('Erro ao executar migrações do banco de dados.');
        }
    }

    private function clearCaches()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('optimize:clear');
    }
}
