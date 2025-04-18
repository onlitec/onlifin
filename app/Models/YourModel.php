<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    public function scopeWithCursor($query, $cursorParams)
    {
        if (isset($cursorParams['position'])) {
            $query->skip($cursorParams['position']);
        }
        
        if (isset($cursorParams['filters'])) {
            foreach ($cursorParams['filters'] as $field => $value) {
                $query->where($field, $value);
            }
        }
        
        return $query;
    }
} 