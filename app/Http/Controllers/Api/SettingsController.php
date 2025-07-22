<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Obter configurações do usuário
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'profile_photo_url' => $user->profile_photo_url,
                    'is_admin' => $user->is_admin,
                    'is_active' => $user->is_active,
                ],
                'notifications' => [
                    'email_notifications' => $user->email_notifications,
                    'whatsapp_notifications' => $user->whatsapp_notifications,
                    'push_notifications' => $user->push_notifications,
                    'due_date_notifications' => $user->due_date_notifications,
                ],
                'security' => [
                    'two_factor_enabled' => $user->two_factor_enabled,
                    'two_factor_confirmed_at' => $user->two_factor_confirmed_at,
                ],
                'social_auth' => [
                    'google_connected' => !empty($user->google_id),
                    'google_avatar' => $user->google_avatar,
                ]
            ]
        ]);
    }

    /**
     * Atualizar perfil do usuário
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->update($request->only(['name', 'phone']));

            return response()->json([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'profile_photo_url' => $user->profile_photo_url,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar foto de perfil
     */
    public function updateProfilePhoto(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Arquivo inválido',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Remover foto anterior se existir
            if ($user->profile_photo && Storage::exists($user->profile_photo)) {
                Storage::delete($user->profile_photo);
            }

            // Salvar nova foto
            $path = $request->file('photo')->store('profile-photos', 'public');
            
            $user->update(['profile_photo' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Foto de perfil atualizada com sucesso',
                'data' => [
                    'profile_photo_url' => $user->profile_photo_url
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar foto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar senha
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar senha atual
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Senha atual incorreta'
            ], 422);
        }

        try {
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // Revogar todos os tokens existentes por segurança
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Senha atualizada com sucesso. Faça login novamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar senha: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar configurações de notificação
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'email_notifications' => 'nullable|boolean',
            'whatsapp_notifications' => 'nullable|boolean',
            'push_notifications' => 'nullable|boolean',
            'due_date_notifications' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->update($request->only([
                'email_notifications',
                'whatsapp_notifications',
                'push_notifications',
                'due_date_notifications'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Configurações de notificação atualizadas com sucesso',
                'data' => [
                    'notifications' => [
                        'email_notifications' => $user->email_notifications,
                        'whatsapp_notifications' => $user->whatsapp_notifications,
                        'push_notifications' => $user->push_notifications,
                        'due_date_notifications' => $user->due_date_notifications,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar configurações: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ativar/desativar autenticação de dois fatores
     */
    public function updateTwoFactor(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar senha
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Senha incorreta'
            ], 422);
        }

        try {
            if ($request->enabled) {
                // Ativar 2FA
                $user->update([
                    'two_factor_enabled' => true,
                    'two_factor_confirmed_at' => now(),
                ]);
                $message = 'Autenticação de dois fatores ativada com sucesso';
            } else {
                // Desativar 2FA
                $user->update([
                    'two_factor_enabled' => false,
                    'two_factor_secret' => null,
                    'two_factor_recovery_codes' => null,
                    'two_factor_confirmed_at' => null,
                ]);
                $message = 'Autenticação de dois fatores desativada com sucesso';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'two_factor_enabled' => $user->two_factor_enabled
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar configuração: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Excluir conta do usuário
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'confirmation' => 'required|string|in:DELETE',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar senha
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Senha incorreta'
            ], 422);
        }

        try {
            // Remover foto de perfil se existir
            if ($user->profile_photo && Storage::exists($user->profile_photo)) {
                Storage::delete($user->profile_photo);
            }

            // Revogar todos os tokens
            $user->tokens()->delete();

            // Desativar conta (soft delete)
            $user->update([
                'is_active' => false,
                'email' => $user->email . '_deleted_' . time(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conta excluída com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir conta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar dados do usuário
     */
    public function exportData(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $data = [
                'user' => $user->only(['name', 'email', 'phone', 'created_at']),
                'accounts' => $user->accounts()->get(),
                'categories' => $user->categories()->get(),
                'transactions' => $user->transactions()->with(['category', 'account'])->get(),
                'exported_at' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Dados exportados com sucesso',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao exportar dados: ' . $e->getMessage()
            ], 500);
        }
    }
}
