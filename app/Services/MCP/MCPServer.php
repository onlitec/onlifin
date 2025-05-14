<?php

namespace App\Services\MCP;

class MCPServer
{
    protected $host;
    protected $port;
    protected $connections = [];
    protected $cursor;
    
    public function __construct(string $host = '127.0.0.1', int $port = 9000)
    {
        $this->host = $host;
        $this->port = $port;
        $this->cursor = new MCPCursor();
    }
    
    public function start()
    {
        // Inicializa o servidor
        $server = stream_socket_server("tcp://{$this->host}:{$this->port}", $errno, $errstr);
        
        if (!$server) {
            throw new \Exception("$errstr ($errno)");
        }
        
        echo "Server listening on {$this->host}:{$this->port}\n";
        
        while ($conn = stream_socket_accept($server)) {
            $this->handleConnection($conn);
        }
    }
    
    public function handleCursorCommand($command)
    {
        switch ($command['action']) {
            case 'next':
                return $this->cursor->next();
            case 'previous':
                return $this->cursor->previous();
            case 'setPosition':
                return $this->cursor->setPosition($command['position']);
            case 'filter':
                return $this->cursor->addFilter($command['field'], $command['value']);
            default:
                throw new \Exception("Unknown cursor command: {$command['action']}");
        }
    }
    
    protected function handleConnection($conn)
    {
        $this->connections[] = $conn;
        
        $data = fread($conn, 1024);
        $command = json_decode($data, true);
        
        if (isset($command['cursor'])) {
            $response = $this->handleCursorCommand($command['cursor']);
            fwrite($conn, json_encode($response));
        }
        
        fclose($conn);
    }
} 