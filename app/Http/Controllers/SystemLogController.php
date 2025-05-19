<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use App\Models\AiCallLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SystemLogController extends Controller
{

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'api'); // Default para 'api'

        if ($tab === 'ai') {
            return $this->aiLogs($request);
        } elseif ($tab === 'laravel') {
            return $this->laravelLogs($request);
        } elseif ($tab === 'system') {
            // Manter funcionalidade existente se necessário, ou remover se não for mais usada diretamente
             return $this->systemLogs($request); // Ou redirecionar para outra aba padrão
        }

        // Por padrão, mostrar logs da API
        return $this->apiLogs($request);
    }
    
    /**
     * Exibe os logs do sistema
     */
    protected function systemLogs(Request $request)
    {
        $query = SystemLog::with('user')->latest();

        // Filtros
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->user}%")
                  ->orWhere('email', 'like', "%{$request->user}%");
            });
        }
        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }
        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        $logs = $query->paginate(50);
        
        // Obter lista de módulos e ações para os filtros
        $modules = SystemLog::distinct()->pluck('module');
        $actions = SystemLog::distinct()->pluck('action');

        return view('settings.logs.index', [
            'activeTab' => 'system',
            'logs' => $logs,
            'modules' => $modules,
            'actions' => $actions,
            'route' => route('settings.logs.index'),
            'exportRoute' => route('settings.logs.export')
        ]);
    }
    
    /**
     * Exibe os logs de monitoramento da API
     */
    protected function apiLogs(Request $request)
    {
        // Diretório de logs de API
        $logDir = storage_path('logs/api_monitor');
        
        // Verifica se o diretório existe
        if (!File::exists($logDir)) {
            File::makeDirectory($logDir, 0755, true);
        }
        
        // Lista todos os arquivos de log
        $logFiles = File::files($logDir);
        $logFiles = array_map(function($file) {
            return [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                'path' => $file->getPathname(),
                'type' => 'api'
            ];
        }, $logFiles);
        
        // Organiza por data de modificação (mais recentes primeiro)
        usort($logFiles, function($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });
        
        return view('settings.logs.api', [
            'activeTab' => 'api',
            'logFiles' => $logFiles,
            'provider' => $request->get('provider', '')
        ]);
    }
    
    /**
     * Exibe os logs do Laravel
     */
    protected function laravelLogs(Request $request)
    {
        // Lista logs do Laravel
        $logFiles = File::files(storage_path('logs'));
        $logFiles = array_map(function($file) {
            return [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                'path' => $file->getPathname(),
                'type' => 'laravel'
            ];
        }, $logFiles);
        
        usort($logFiles, function($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });
        
        return view('settings.logs.laravel', [
            'activeTab' => 'laravel',
            'logFiles' => $logFiles
        ]);
    }

    /**
     * Mostrar o conteúdo de um arquivo de log
     */
    public function view($log)
    {
        $filePath = storage_path('logs/' . $log);
        
        if (!File::exists($filePath)) {
            return redirect()->route('settings.logs.index', ['tab' => 'laravel'])
                ->with('error', 'Arquivo de log não encontrado');
        }
        
        $content = File::get($filePath);
        
        return view('settings.logs.view', [
            'type' => 'laravel',
            'filename' => $log,
            'content' => $content,
            'entries' => []
        ]);
    }
    
    public function show(SystemLog $log)
    {
        return view('settings.logs.show', compact('log'));
    }


    public function export(Request $request)
    {
        $query = SystemLog::with('user')->latest();

        // Aplicar os mesmos filtros do index
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->user}%")
                  ->orWhere('email', 'like', "%{$request->user}%");
            });
        }
        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }
        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        $logs = $query->get();

        // Criar arquivo CSV
        $filename = 'system_logs_' . now()->format('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'r+');
        
        // Cabeçalho
        fputcsv($handle, ['Data', 'Usuário', 'Módulo', 'Ação', 'Descrição', 'IP', 'Navegador']);

        // Dados
        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->created_at->format('d/m/Y H:i:s'),
                $log->user ? $log->user->name : 'Sistema',
                $log->module,
                $log->action,
                $log->description,
                $log->ip_address,
                $log->user_agent
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"$filename\"");
    }

    /**
     * Exibe os logs de chamadas de IA
     */
    protected function aiLogs(Request $request)
    {
        $query = AiCallLog::with('user')->latest();

        // Adicionar filtros se necessário (exemplo: por usuário, provider, status)
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }
        if ($request->filled('status_code')) {
            $query->where('status_code', $request->status_code);
        }
         if ($request->filled('user')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->user}%")
                  ->orWhere('email', 'like', "%{$request->user}%");
            });
        }
        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }
        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        $logs = $query->paginate(50); // Paginação

        // Obter lista de providers e status codes para filtros
        $providers = AiCallLog::distinct()->pluck('provider');
        $statusCodes = AiCallLog::distinct()->pluck('status_code');

        return view('settings.logs.ai', [
            'activeTab' => 'ai',
            'logs' => $logs,
            'providers' => $providers,
            'statusCodes' => $statusCodes,
            'filters' => $request->only(['provider', 'status_code', 'user', 'date_start', 'date_end']) // Passar filtros para a view
        ]);
    }

    /**
     * Lista todos os arquivos de log do sistema
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function files(Request $request)
    {
        // Lista logs do Laravel
        $laravelLogFiles = File::files(storage_path('logs'));
        $laravelLogFiles = array_map(function($file) {
            return [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                'path' => $file->getPathname(),
                'type' => 'laravel'
            ];
        }, $laravelLogFiles);
        
        // Lista logs da API
        $apiLogDir = storage_path('logs/api_monitor');
        $apiLogFiles = [];
        
        if (File::exists($apiLogDir)) {
            $apiLogFiles = File::files($apiLogDir);
            $apiLogFiles = array_map(function($file) {
                return [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                    'path' => $file->getPathname(),
                    'type' => 'api'
                ];
            }, $apiLogFiles);
        }
        
        // Combina os logs e ordena por data (mais recentes primeiro)
        $logFiles = array_merge($laravelLogFiles, $apiLogFiles);
        usort($logFiles, function($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });
        
        return view('settings.logs.files', [
            'activeTab' => 'files',
            'logFiles' => $logFiles
        ]);
    }
} 