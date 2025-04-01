<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\NotificationSetting;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use App\Notifications\TestNotification;

class NotificationController extends Controller
{
    /**
     * Mostrar painel de notificações
     */
    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->paginate(10);
        $unreadCount = $user->unreadNotifications()->count();
        
        return view('notifications.index', compact('notifications', 'unreadCount'));
    }
    
    /**
     * Mostrar configurações de notificação
     */
    public function settings()
    {
        $user = Auth::user();
        $settings = NotificationSetting::getOrCreate($user->id);
        
        return view('notifications.settings', compact('settings'));
    }
    
    /**
     * Atualizar configurações de notificação
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();
        
        $validatedData = $request->validate([
            'email_enabled' => 'boolean',
            'database_enabled' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'notification_preferences' => 'array',
            'mark_all_read' => 'boolean',
        ]);
        
        // Se solicitado, marcar todas as notificações como lidas
        if ($request->boolean('mark_all_read')) {
            $user->unreadNotifications->markAsRead();
            return redirect()->back()->with('success', 'Todas as notificações foram marcadas como lidas.');
        }
        
        $settings = NotificationSetting::getOrCreate($user->id);
        $settings->update($validatedData);
        
        return redirect()->back()->with('success', 'Configurações de notificação atualizadas com sucesso!');
    }
    
    /**
     * Obter notificações não lidas (usado pelo polling do JS)
     */
    public function getUnreadNotifications()
    {
        $user = auth()->user();
        $notifications = $user->unreadNotifications->map(function ($notification) {
            $data = $notification->data;
            
            return [
                'id' => $notification->id,
                'title' => $data['title'] ?? 'Nova notificação',
                'message' => $data['message'] ?? '',
                'action_url' => $data['action_url'] ?? null,
                'action_text' => $data['action_text'] ?? null,
                'image' => $data['image'] ?? null,
                'created_at' => $notification->created_at->diffForHumans()
            ];
        });
        
        return response()->json([
            'success' => true,
            'count' => $notifications->count(),
            'notifications' => $notifications
        ]);
    }
    
    /**
     * Marcar notificações como lidas
     */
    public function markAsRead(Request $request)
    {
        $user = auth()->user();
        $notificationIds = $request->input('notification_ids', []);
        
        if (empty($notificationIds)) {
            // Marcar todas como lidas
            $user->unreadNotifications->markAsRead();
        } else {
            // Marcar somente as especificadas
            $user->notifications()->whereIn('id', $notificationIds)->update(['read_at' => now()]);
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Testar envio de notificação
     */
    public function testNotification(Request $request)
    {
        $user = Auth::user();
        
        $channels = $request->input('channels', ['mail', 'database']);
        
        $notification = new TestNotification($channels);
        $user->notify($notification);
        
        return redirect()->back()->with('success', 'Notificação de teste enviada com sucesso!');
    }
    
    /**
     * Enviar notificação para todos os usuários
     */
    public function sendToAll(Request $request)
    {
        if (!Auth::user()->is_admin) {
            return redirect()->back()->with('error', 'Permissão negada.');
        }
        
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'action_url' => 'nullable|url',
            'action_text' => 'nullable|string|max:255',
            'image' => 'nullable|url',
            'channels' => 'required|array',
            'channels.*' => 'string|in:mail,database,whatsapp',
        ]);
        
        $users = User::where('is_active', true)->get();
        
        foreach ($users as $user) {
            $notification = new TestNotification(
                $validatedData['channels'],
                $validatedData['title'],
                $validatedData['message'],
                $validatedData['action_url'] ?? null,
                $validatedData['action_text'] ?? null,
                $validatedData['image'] ?? null
            );
            
            $user->notify($notification);
        }
        
        return redirect()->back()->with('success', 'Notificação enviada para todos os usuários com sucesso!');
    }
} 