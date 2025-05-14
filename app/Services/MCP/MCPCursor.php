<?php

namespace App\Services\MCP;

class MCPCursor
{
    protected $position = 0;
    protected $data = [];
    protected $filters = [];
    
    public function setPosition(int $position)
    {
        $this->position = $position;
        return $this;
    }
    
    public function addFilter(string $field, $value)
    {
        $this->filters[$field] = $value;
        return $this;
    }
    
    public function next()
    {
        $this->position++;
        return $this->current();
    }
    
    public function previous()
    {
        $this->position--;
        return $this->current();
    }
    
    public function current()
    {
        return $this->data[$this->position] ?? null;
    }
    
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }
} 