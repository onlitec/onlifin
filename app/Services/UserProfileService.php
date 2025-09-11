<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserNotificationSettings;
use App\Models\UserPrivacySettings;
use App\Models\UserProfileAudit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Service para gerenciamento de perfil do usuário
 * 
 * Responsável por:
 * - Operações CRUD do perfil
 * - Upload e gerenciamento de avatar
 * - Configurações de notificação e privacidade
 * - Histórico de alterações
 */
class UserProfileService
{
    /**
     * Obter perfil completo do usuário
     */
    public function getUserProfile(User $user): array
    {
        $user->load(['profile', 'notificationSettings', 'privacySettings']);
        
        return [
            'user' => $user,
            'profile' => $user->profile,
            'notification_settings' => $user->notificationSettings,
            'privacy_settings' => $user->privacySettings
        ];
    }

    /**
     * Atualizar perfil do usuário
     */
    public function updateProfile(User $user, array $data): UserProfile
    {
        DB::beginTransaction();
        
        try {
            $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);
            
            // Capturar valores antigos para auditoria
            $oldValues = $profile->exists ? $profile->toArray() : [];
            
            $profile->fill($data);
            $profile->save();
            
            // Registrar auditoria
            UserProfileAudit::logProfileUpdate(
                $user->id,
                $oldValues,
                $profile->toArray()
            );
            
            Log::info('Perfil atualizado com sucesso', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($data)
            ]);
            
            DB::commit();
            return $profile;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar perfil', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Upload de avatar
     */
    public function uploadAvatar(User $user, UploadedFile $file): string
    {
        try {
            // Remover avatar anterior se existir
            if ($user->profile && $user->profile->avatar_path) {
                Storage::disk('public')->delete($user->profile->avatar_path);
            }
            
            // Fazer upload do novo avatar
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('avatars', $filename, 'public');
            
            // Atualizar perfil com novo caminho
            if (!$user->profile) {
                $user->profile()->create(['avatar_path' => $path]);
            } else {
                $user->profile->update(['avatar_path' => $path]);
            }
            
            // Registrar auditoria
            UserProfileAudit::logAvatarUpload($user->id, $filename);
            
            Log::info('Avatar atualizado', [
                'user_id' => $user->id,
                'avatar_path' => $path
            ]);
            
            return Storage::url($path);
            
        } catch (\Exception $e) {
            Log::error('Erro ao fazer upload do avatar', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remover avatar
     */
    public function removeAvatar(User $user): bool
    {
        try {
            if ($user->profile && $user->profile->avatar_path) {
                $oldFileName = basename($user->profile->avatar_path);
                Storage::disk('public')->delete($user->profile->avatar_path);
                $user->profile->update(['avatar_path' => null]);
                
                // Registrar auditoria
                UserProfileAudit::logAvatarRemove($user->id, $oldFileName);
                
                Log::info('Avatar removido', ['user_id' => $user->id]);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('Erro ao remover avatar', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Atualizar configurações de notificação
     */
    public function updateNotificationSettings(User $user, array $data)
    {
        try {
            $settings = $user->notificationSettings;
            
            if (!$settings) {
                $settings = new UserNotificationSettings(['user_id' => $user->id]);
            }
            
            $settings->fill($data);
            $settings->save();
            
            Log::info('Configurações de notificação atualizadas', [
                'user_id' => $user->id,
                'settings' => $data
            ]);
            
            return $settings;
            
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar configurações de notificação', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Atualizar configurações de privacidade
     */
    public function updatePrivacySettings(User $user, array $data)
    {
        try {
            $settings = $user->privacySettings;
            
            if (!$settings) {
                $settings = new UserPrivacySettings(['user_id' => $user->id]);
            }
            
            $settings->fill($data);
            $settings->save();
            
            Log::info('Configurações de privacidade atualizadas', [
                'user_id' => $user->id,
                'settings' => $data
            ]);
            
            return $settings;
            
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar configurações de privacidade', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Verificar senha atual
     */
    public function verifyCurrentPassword(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }

    /**
     * Excluir conta do usuário
     */
    public function deleteAccount(User $user): bool
    {
        DB::beginTransaction();
        
        try {
            // Remover avatar se existir
            if ($user->profile && $user->profile->avatar_path) {
                Storage::disk('public')->delete($user->profile->avatar_path);
            }
            
            // Excluir registros relacionados
            $user->profile?->delete();
            $user->notificationSettings?->delete();
            $user->privacySettings?->delete();
            
            // Excluir usuário
            $user->delete();
            
            Log::info('Conta do usuário excluída', ['user_id' => $user->id]);
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao excluir conta', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obter histórico de alterações do perfil
     */
    public function getChangeHistory(User $user, int $limit = 50): array
    {
        // Implementar quando a tabela de histórico for criada
        return [];
    }
}