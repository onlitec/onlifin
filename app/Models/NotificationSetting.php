<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_enabled',
        'database_enabled',
        'whatsapp_enabled',
        'push_enabled',
        'muted_categories',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'database_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'muted_categories' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verificar se uma categoria específica está silenciada
     */
    public function isCategoryMuted($category)
    {
        if (empty($this->muted_categories)) {
            return false;
        }

        return in_array($category, $this->muted_categories);
    }
} 