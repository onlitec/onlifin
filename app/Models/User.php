<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'notifications_email',
        'notifications_whatsapp',
        'password',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'notifications_email' => 'boolean',
        'notifications_whatsapp' => 'boolean',
    ];

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
     * Rota para canal de notificação WhatsApp
     */
    public function routeNotificationForTwilio()
    {
        if ($this->notifications_whatsapp && $this->phone) {
            // Formatar para padrão internacional
            $phone = preg_replace('/[^0-9]/', '', $this->phone);
            
            // Adicionar prefixo whatsapp: se necessário
            if (config('services.twilio.whatsapp', true)) {
                return 'whatsapp:+' . $phone;
            }
            
            return '+' . $phone;
        }
        
        return null;
    }
    
    /**
     * Determina se o usuário deve receber uma notificação
     */
    public function shouldReceiveNotification($notification)
    {
        if (method_exists($notification, 'via')) {
            $channels = $notification->via($this);
            
            if (in_array('mail', $channels) && !$this->notifications_email) {
                return false;
            }
            
            if (in_array(\NotificationChannels\Twilio\TwilioChannel::class, $channels) && !$this->notifications_whatsapp) {
                return false;
            }
        }
        
        return true;
    }
}
