<?php

namespace App\Providers;

use App\Services\MCP\MCPServer;
use Illuminate\Support\ServiceProvider;

class MCPServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(MCPServer::class, function ($app) {
            return new MCPServer(
                config('mcp.host', '127.0.0.1'),
                config('mcp.port', 9000)
            );
        });
    }
} 