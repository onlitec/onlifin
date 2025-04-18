<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_enabled',
        'database_enabled',
        'whatsapp_enabled',
        'push_enabled',
        'whatsapp_provider',
        'notification_preferences',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'database_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'notification_preferences' => 'array',
    ];

    /**
     * Get the user that owns the notification settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create settings for a user
     */
    public static function getOrCreate(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'email_enabled' => true,
                'database_enabled' => true,
                'whatsapp_enabled' => false,
                'push_enabled' => false,
            ]
        );
    }

    /**
     * Verificar se uma categoria específica está silenciada
     */
    public function isCategoryMuted($category)
    {
        if (empty($this->notification_preferences)) {
            return false;
        }

        return in_array($category, $this->notification_preferences);
    }
} 