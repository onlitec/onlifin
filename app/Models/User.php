<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'phone',
        'is_active',
        'email_verified_at',
        'email_notifications',
        'whatsapp_notifications',
        'push_notifications',
        'due_date_notifications',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Adicionar um método boot para log
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function($user) {
            Log::info('Criando usuário no modelo', [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active
            ]);
        });
        
        static::created(function($user) {
            Log::info('Usuário criado no modelo', [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);
        });
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasPermission($permission): bool
    {
        return $this->roles()->whereHas('permissions', function ($query) use ($permission) {
            $query->where('name', $permission);
        })->exists();
    }

    /**
     * Route notifications for the WhatsApp channel.
     */
    public function routeNotificationForWhatsapp()
    {
        return $this->phone;
    }

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail()
    {
        return $this->email;
    }

    /**
     * Get notification settings for the user
     */
    public function notificationSettings()
    {
        return $this->hasOne(NotificationSetting::class);
    }

    /**
     * Get due date notification settings for the user
     */
    public function dueDateNotificationSettings()
    {
        return $this->hasOne(DueDateNotificationSetting::class);
    }

    /**
     * Determine if the user should receive notifications via the given channel.
     */
    public function shouldReceiveNotification($channel)
    {
        // Se não tiver configurações, usar padrões
        if (!$this->notificationSettings) {
            return in_array($channel, ['mail', 'database']);
        }

        // Verificar se o canal está habilitado
        switch ($channel) {
            case 'mail':
                return (bool) $this->notificationSettings->email_enabled;
            case 'database':
                return (bool) $this->notificationSettings->database_enabled;
            case 'whatsapp':
                return (bool) $this->notificationSettings->whatsapp_enabled && !empty($this->phone);
            case 'push':
                return (bool) $this->notificationSettings->push_enabled;
            default:
                return true;
        }
    }
    
    /**
     * Determine if the user should receive WhatsApp notifications.
     */
    public function shouldReceiveWhatsApp()
    {
        return $this->whatsapp_notifications && !empty($this->phone);
    }
}
