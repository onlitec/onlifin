<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SystemLogController extends Controller
{

    public function index(Request $request)
    {
        // Exibir diretamente os logs da API
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
    public function view(Request $request, $type, $filename)
    {
        $filePath = '';
        $content = '';
        $entries = [];
        
        if ($type === 'api') {
            $filePath = storage_path('logs/api_monitor/' . $filename);
            
            if (File::exists($filePath)) {
                $content = File::get($filePath);
                
                // Processar entradas de log de API
                $rawEntries = explode("---END-CALL---\n", $content);
                foreach ($rawEntries as $entry) {
                    $entry = trim($entry);
                    if (empty($entry)) continue;
                    
                    try {
                        $data = json_decode($entry, true);
                        if ($data) {
                            $entries[] = $data;
                        }
                    } catch (\Exception $e) {
                        // Ignora entradas com formato inválido
                    }
                }
            }
        } elseif ($type === 'laravel') {
            $filePath = storage_path('logs/' . $filename);
            if (File::exists($filePath)) {
                $content = File::get($filePath);
            }
        }
        
        if (empty($filePath) || !File::exists($filePath)) {
            return redirect()->route('settings.logs.index', ['tab' => $type])
                ->with('error', 'Arquivo de log não encontrado');
        }
        
        return view('settings.logs.view', [
            'type' => $type,
            'filename' => $filename,
            'content' => $content,
            'entries' => $entries
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
} 