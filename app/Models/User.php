<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Company;
use Illuminate\Support\Facades\Session;
use App\Models\Account;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles {
        HasRoles::hasPermissionTo as traitHasPermissionTo;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo',
        'is_admin',
        'phone',
        'is_active',
        'email_verified_at',
        'email_notifications',
        'whatsapp_notifications',
        'push_notifications',
        'due_date_notifications',
        'google_id',
        'google_avatar',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_recovery_codes' => 'array',
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
        // Usuário é administrador global se coluna is_admin ou cargo 'Administrador'
        return (bool) $this->is_admin || $this->roles()->where('name', 'Administrador')->exists();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Verifica se o usuário possui todas as permissões-chave para acesso total.
     */
    public function isSuperUser(): bool
    {
        $requiredPermissions = [
            'view_users', 'create_users', 'edit_users', 'delete_users',
            'view_roles', 'manage_roles',
            'view_all_transactions', 'view_own_transactions', 'create_transactions',
            'edit_all_transactions', 'edit_own_transactions',
            'delete_all_transactions', 'delete_own_transactions',
            'mark_as_paid_all_transactions', 'mark_as_paid_own_transactions',
            'view_all_accounts', 'view_own_accounts', 'create_accounts',
            'edit_all_accounts', 'edit_own_accounts',
            'delete_all_accounts', 'delete_own_accounts',
            'view_all_categories', 'view_own_categories', 'create_categories',
            'edit_all_categories', 'edit_own_categories',
            'delete_all_categories', 'delete_own_categories',
            'view_reports', 'manage_backups', 'manage_settings',
        ];
        foreach ($requiredPermissions as $perm) {
            if (!$this->hasPermission($perm)) {
                return false;
            }
        }
        return true;
    }

    // Sobrescrevo hasPermissionTo do trait para considerar administradores globais
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // Administradores globais têm acesso a todas as permissões
        if ($this->isAdmin()) {
            return true;
        }
        // Chamo implementação original do trait
        return $this->traitHasPermissionTo($permission, $guardName);
    }

    /**
     * Alias para compatibilidade: permite checar permissão usando hasPermission
     */
    public function hasPermission($permission, $guardName = null): bool
    {
        return $this->hasPermissionTo($permission, $guardName);
    }

    public function hasRole(string $roleName): bool
    {
        // Administradores globais têm acesso a todos os papéis
        if ($this->isAdmin()) {
            return true;
        }
        return $this->roles->contains('name', $roleName);
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

    /**
     * Get the URL of the user's profile photo or default placeholder.
     */
    public function getProfilePhotoUrlAttribute()
    {
        return $this->profile_photo
            ? Storage::url($this->profile_photo)
            : asset('assets/svg/default-avatar.svg');
    }

    /**
     * Relacionamento: Contas bancárias associadas ao usuário.
     */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Relacionamento: Contas bancárias acessíveis através dos grupos do usuário.
     */
    public function accountsThroughGroups()
    {
        $groupIds = $this->groups()->pluck('groups.id');
        return Account::whereIn('group_id', $groupIds);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    /**
     * Usuário proprietário das empresas
     */
    public function ownedCompanies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Company::class, 'owner_id');
    }

    /**
     * Retorna todas as empresas associadas ao usuário
     */
    public function allCompanies(): \Illuminate\Support\Collection
    {
        return $this->ownedCompanies()->get();
    }

    /**
     * Retorna a empresa pessoal do usuário, marcada como personal_company
     */
    public function personalCompany(): ?Company
    {
        return $this->ownedCompanies()->where('personal_company', true)->first();
    }

    /**
     * Determine se o usuário pertence à empresa (é proprietário).
     */
    public function belongsToCompany(Company $company): bool
    {
        return $this->ownedCompanies()->where('id', $company->id)->exists();
    }

    /**
     * Alterna a empresa atual do usuário (apenas em sessão).
     */
    public function switchCompany(Company $company): bool
    {
        if (! $this->belongsToCompany($company)) {
            return false;
        }

        Session::put('current_company_id', $company->id);
        $this->setRelation('currentCompany', $company);

        return true;
    }

    /**
     * Retorna a empresa atual do usuário, definida na sessão ou a empresa pessoal por padrão.
     *
     * @return Company|null
     */
    public function getCurrentCompanyAttribute(): ?Company
    {
        $companyId = Session::get('current_company_id');

        if ($companyId) {
            // Tenta carregar a empresa da sessão
            $company = $this->ownedCompanies()->where('id', $companyId)->first();
            if ($company) {
                return $company;
            }
        }

        // Fallback: retorna a empresa pessoal marcada
        $personal = $this->personalCompany();
        if ($personal) {
            Session::put('current_company_id', $personal->id);
            return $personal;
        }

        // Fallback adicional: retorna primeira empresa disponível
        $firstCompany = $this->ownedCompanies()->first();
        if ($firstCompany) {
            Session::put('current_company_id', $firstCompany->id);
            return $firstCompany;
        }

        return null;
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user');
    }

    public function currentCompany()
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    /**
     * Verifica se o usuário tem 2FA habilitado e confirmado
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Gera códigos de recuperação para 2FA
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(str_replace('-', '', (string) \Illuminate\Support\Str::uuid()));
        }
        
        $this->two_factor_recovery_codes = $codes;
        $this->save();
        
        return $codes;
    }

    /**
     * Verifica se um código de recuperação é válido
     */
    public function isValidRecoveryCode(string $code): bool
    {
        if (!$this->two_factor_recovery_codes) {
            return false;
        }
        
        return in_array(strtoupper($code), $this->two_factor_recovery_codes);
    }

    /**
     * Usa um código de recuperação (remove da lista)
     */
    public function useRecoveryCode(string $code): bool
    {
        if (!$this->isValidRecoveryCode($code)) {
            return false;
        }
        
        $codes = $this->two_factor_recovery_codes;
        $key = array_search(strtoupper($code), $codes);
        
        if ($key !== false) {
            unset($codes[$key]);
            $this->two_factor_recovery_codes = array_values($codes);
            $this->save();
            return true;
        }
        
        return false;
    }

    /**
     * Verifica se o usuário fez login via Google
     */
    public function isGoogleUser(): bool
    {
        return !empty($this->google_id);
    }

    /**
     * Relacionamento com contas sociais
     */
    public function socialAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Obtém conta social por provedor
     */
    public function getSocialAccount(string $provider): ?SocialAccount
    {
        return $this->socialAccounts()->where('provider', $provider)->first();
    }

    /**
     * Verifica se o usuário tem conta vinculada a um provedor
     */
    public function hasSocialProvider(string $provider): bool
    {
        return $this->socialAccounts()->where('provider', $provider)->exists();
    }

    /**
     * Obtém todos os provedores sociais vinculados
     */
    public function getConnectedProviders(): array
    {
        return $this->socialAccounts()->pluck('provider')->toArray();
    }

    /**
     * Verifica se o usuário tem pelo menos uma conta social vinculada
     */
    public function hasSocialAccounts(): bool
    {
        return $this->socialAccounts()->exists();
    }

    /**
     * Verifica se um email está cadastrado no sistema
     */
    public static function isEmailRegistered(string $email): bool
    {
        return self::where('email', $email)->exists();
    }

    /**
     * Busca usuário por email
     */
    public static function findByEmail(string $email): ?User
    {
        return self::where('email', $email)->first();
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
