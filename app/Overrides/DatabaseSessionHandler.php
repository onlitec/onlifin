<?php

namespace App\Overrides;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Session\DatabaseSessionHandler as BaseDatabaseSessionHandler;

class DatabaseSessionHandler extends BaseDatabaseSessionHandler
{
    /**
     * Construtor com os mesmos parâmetros do handler original.
     */
    public function __construct(ConnectionInterface $connection, $table, $minutes, ?Container $container = null)
    {
        parent::__construct($connection, $table, $minutes, $container);
    }

    /**
     * Sobrescreve o método read para garantir escape adequado do sessionId.
     * 
     * @param string $sessionId
     * @return string|false
     */
    public function read($sessionId): string|false
    {
        // Use a query builder para garantir que os parâmetros sejam adequadamente escapados
        $session = $this->getQuery()
            ->where('id', $sessionId) // Isto garante o valor será passado como parâmetro e escapado
            ->first();
            
        $session = (object) ($session ?: []);

        if ($this->expired($session)) {
            $this->exists = true;

            return '';
        }

        if (isset($session->payload)) {
            $this->exists = true;

            return base64_decode($session->payload);
        }

        return '';
    }

    /**
     * Sobrescreve o método de destruição para garantir escape adequado do sessionId.
     * 
     * @param string $sessionId
     * @return bool
     */
    public function destroy($sessionId): bool
    {
        $this->getQuery()->where('id', $sessionId)->delete();

        return true;
    }
} 