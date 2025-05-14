<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LogLaravel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:show {lines=50 : Number of lines to show} {--C|clear : Clear log files after showing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show latest Laravel log entries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logPath = storage_path('logs');
        $logFiles = File::glob($logPath . '/laravel-*.log');
        
        if (empty($logFiles)) {
            $this->error('No log files found in storage/logs directory.');
            return 1;
        }
        
        // Sort by date modified, newest first
        usort($logFiles, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $latestLogFile = $logFiles[0];
        $logContents = file_get_contents($latestLogFile);
        
        if (empty($logContents)) {
            $this->info("Log file {$latestLogFile} is empty.");
            return 0;
        }
        
        $lines = explode("\n", $logContents);
        $numLines = (int) $this->argument('lines');
        $lastLines = array_slice($lines, max(0, count($lines) - $numLines));
        
        $this->info("Showing last {$numLines} lines from " . basename($latestLogFile) . ":\n");
        
        foreach ($lastLines as $line) {
            if (str_contains($line, 'error') || str_contains($line, 'ERROR') || str_contains($line, 'Exception')) {
                $this->error($line);
            } else if (str_contains($line, 'warning') || str_contains($line, 'WARNING')) {
                $this->warn($line);
            } else if (str_contains($line, 'info') || str_contains($line, 'INFO')) {
                $this->info($line);
            } else {
                $this->line($line);
            }
        }
        
        if ($this->option('clear')) {
            File::put($latestLogFile, '');
            $this->info("\nLog file has been cleared.");
        }
        
        return 0;
    }
}
