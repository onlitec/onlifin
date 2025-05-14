<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LogViewerController extends Controller
{
    /**
     * Mostra a lista de arquivos de log
     */
    public function index()
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
            ];
        }, $logFiles);
        
        // Organiza por data de modificação (mais recentes primeiro)
        usort($logFiles, function($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });
        
        // Lista também logs do Laravel
        $laravelLogs = File::files(storage_path('logs'));
        $laravelLogs = array_map(function($file) {
            return [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                'path' => $file->getPathname(),
            ];
        }, $laravelLogs);
        
        usort($laravelLogs, function($a, $b) {
            return strtotime($b['modified']) - strtotime($a['modified']);
        });
        
        return view('settings.logs.index', [
            'apiLogs' => $logFiles,
            'laravelLogs' => $laravelLogs
        ]);
    }
    
    /**
     * Mostra o conteúdo de um arquivo de log
     */
    public function show(Request $request, $type, $filename)
    {
        $filePath = '';
        
        if ($type === 'api') {
            $filePath = storage_path('logs/api_monitor/' . $filename);
        } elseif ($type === 'laravel') {
            $filePath = storage_path('logs/' . $filename);
        }
        
        if (!File::exists($filePath)) {
            return redirect()->route('settings.logs.index')
                ->with('error', 'Arquivo de log não encontrado');
        }
        
        $content = File::get($filePath);
        
        // Para logs de API, formata o conteúdo JSON
        $formatted = [];
        if ($type === 'api') {
            $entries = explode('---END-CALL---', $content);
            foreach ($entries as $entry) {
                $entry = trim($entry);
                if (empty($entry)) continue;
                
                try {
                    $data = json_decode($entry, true);
                    if ($data) {
                        $formatted[] = $data;
                    }
                } catch (\Exception $e) {
                    // Ignora entradas com formato inválido
                }
            }
        }
        
        return view('settings.logs.show', [
            'type' => $type,
            'filename' => $filename,
            'content' => $content,
            'formatted' => $formatted
        ]);
    }
    
    /**
     * Deleta um arquivo de log
     */
    public function delete(Request $request, $type, $filename)
    {
        $filePath = '';
        
        if ($type === 'api') {
            $filePath = storage_path('logs/api_monitor/' . $filename);
        } elseif ($type === 'laravel') {
            $filePath = storage_path('logs/' . $filename);
        }
        
        if (File::exists($filePath)) {
            File::delete($filePath);
            return redirect()->route('settings.logs.index')
                ->with('success', 'Log excluído com sucesso');
        }
        
        return redirect()->route('settings.logs.index')
            ->with('error', 'Arquivo de log não encontrado');
    }
}
