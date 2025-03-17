<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'value',
        'user_id',
    ];

    /**
     * Obter o usuário associado a esta configuração.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 