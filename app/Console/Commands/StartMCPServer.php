<?php

namespace App\Console\Commands;

use App\Services\MCP\MCPServer;
use Illuminate\Console\Command;

class StartMCPServer extends Command
{
    protected $signature = 'mcp:serve';
    protected $description = 'Start the MCP server';

    public function handle(MCPServer $server)
    {
        $this->info('Starting MCP server...');
        $server->start();
    }
} 