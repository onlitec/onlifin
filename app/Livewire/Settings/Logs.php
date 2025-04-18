<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class Logs extends Component
{
    public $logs = [];
    public $selectedDate = null;
    public $search = '';
    public $logFiles = [];
    
    public function mount()
    {
        $this->loadLogFiles();
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadLogs();
    }
    
    public function loadLogFiles()
    {
        $logPath = storage_path('logs');
        $files = File::files($logPath);
        
        $this->logFiles = collect($files)
            ->filter(function($file) {
                return $file->getExtension() === 'log' && 
                       $file->getFilename() !== 'laravel.log';
            })
            ->map(function($file) {
                return [
                    'name' => $file->getFilename(),
                    'date' => str_replace('laravel-', '', $file->getFilename()),
                    'size' => number_format($file->getSize() / 1024, 2) . ' KB'
                ];
            })
            ->sortByDesc('date')
            ->values()
            ->toArray();
    }
    
    public function loadLogs()
    {
        $logPath = storage_path('logs/laravel-' . $this->selectedDate . '.log');
        
        if (File::exists($logPath)) {
            $content = File::get($logPath);
            
            $this->logs = collect(explode(PHP_EOL, $content))
                ->filter()
                ->map(function($line) {
                    return trim($line);
                })
                ->filter(function($line) {
                    return str_contains($line, $this->search);
                })
                ->values()
                ->toArray();
        } else {
            $this->logs = [];
        }
    }
    
    public function updatedSearch()
    {
        $this->loadLogs();
    }
    
    public function render()
    {
        return view('livewire.settings.logs');
    }
}
