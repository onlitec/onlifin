<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'value',
        'user_id',
        // Configurações de Email
        'email_notifications_enabled',
        'email_notify_new_transactions',
        'email_notify_due_dates',
        'email_notify_low_balance',
        'email_low_balance_threshold',
        // Configurações de WhatsApp
        'whatsapp_notifications_enabled',
        'whatsapp_number',
        'whatsapp_notify_new_transactions',
        'whatsapp_notify_due_dates',
        'whatsapp_notify_low_balance',
        'whatsapp_low_balance_threshold',
    ];

    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'email_notifications_enabled' => 'boolean',
        'email_notify_new_transactions' => 'boolean',
        'email_notify_due_dates' => 'boolean',
        'email_notify_low_balance' => 'boolean',
        'email_low_balance_threshold' => 'decimal:2',
        'whatsapp_notifications_enabled' => 'boolean',
        'whatsapp_notify_new_transactions' => 'boolean',
        'whatsapp_notify_due_dates' => 'boolean',
        'whatsapp_notify_low_balance' => 'boolean',
        'whatsapp_low_balance_threshold' => 'decimal:2',
    ];

    /**
     * Obter o usuário associado a esta configuração.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retrieve a setting value by key or return default
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Create or update a setting value by key
     */
    public static function set(string $key, $value)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
} 