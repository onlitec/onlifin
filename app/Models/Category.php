<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'type', // 'expense' ou 'income'
        'color',
        'description',
        'icon',
        'user_id'
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 