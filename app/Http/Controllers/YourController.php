<?php

namespace App\Http\Controllers;

use App\Services\MCP\MCPServer;

class YourController extends Controller
{
    protected $mcpServer;
    
    public function __construct(MCPServer $mcpServer)
    {
        $this->mcpServer = $mcpServer;
    }
    
    public function example()
    {
        // Exemplo de comando para o cursor
        $command = [
            'cursor' => [
                'action' => 'next'
            ]
        ];
        
        // O servidor processará o comando e retornará o próximo item
        $response = $this->mcpServer->handleCursorCommand($command);
        
        return response()->json($response);
    }
} 