<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use Illuminate\Http\Request;

class SystemLogController extends Controller
{
    public function index(Request $request)
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

        return view('settings.logs.index', compact('logs', 'modules', 'actions'));
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