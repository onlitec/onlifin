<?php

return [
    'host' => env('MCP_HOST', '127.0.0.1'),
    'port' => env('MCP_PORT', 9000),
    'max_connections' => env('MCP_MAX_CONNECTIONS', 10),
    'timeout' => env('MCP_TIMEOUT', 30),
]; 