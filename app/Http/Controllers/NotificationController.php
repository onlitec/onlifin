<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\NotificationSetting;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Notification;

class NotificationController extends Controller
{
    /**
     * Mostrar painel de notificações
     */
    public function index()
    {
        $user = auth()->user();
        $notifications = $user->notifications()->paginate(10);
        $unreadCount = $user->unreadNotifications()->count();
        
        return view('notifications.index', compact('notifications', 'unreadCount'));
    }
    
    /**
     * Mostrar configurações de notificação
     */
    public function settings()
    {
        $user = auth()->user();
        $settings = $user->notificationSettings ?? new NotificationSetting();
        
        return view('notifications.settings', compact('settings'));
    }
    
    /**
     * Atualizar configurações de notificação
     */
    public function updateSettings(Request $request)
    {
        $user = auth()->user();
        
        $validatedData = $request->validate([
            'email_enabled' => 'boolean',
            'database_enabled' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'muted_categories' => 'nullable|array',
        ]);
        
        // Criar ou atualizar configurações
        if ($user->notificationSettings) {
            $user->notificationSettings->update($validatedData);
        } else {
            $user->notificationSettings()->create($validatedData);
        }
        
        return redirect()->back()->with('success', 'Configurações de notificação atualizadas com sucesso.');
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
        if (!auth()->user()->is_admin) {
            return redirect()->back()->with('error', 'Apenas administradores podem enviar notificações de teste.');
        }
        
        $channels = $request->input('channels', ['mail', 'database']);
        
        if (in_array('whatsapp', $channels) && empty(auth()->user()->phone)) {
            return redirect()->back()->with('error', 'Você precisa ter um número de telefone para testar notificações por WhatsApp.');
        }
        
        $notification = new SystemNotification(
            'Notificação de Teste',
            'Esta é uma notificação de teste enviada em ' . now()->format('d/m/Y H:i:s'),
            $channels,
            route('notifications.index'),
            'Ver todas notificações'
        );
        
        auth()->user()->notify($notification);
        
        return redirect()->back()->with('success', 'Notificação de teste enviada com sucesso!');
    }
    
    /**
     * Enviar notificação para todos os usuários
     */
    public function sendToAll(Request $request)
    {
        if (!auth()->user()->is_admin) {
            return redirect()->back()->with('error', 'Apenas administradores podem enviar notificações em massa.');
        }
        
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'action_url' => 'nullable|url',
            'action_text' => 'nullable|string|max:255',
            'channels' => 'required|array',
            'image' => 'nullable|url'
        ]);
        
        $notification = new SystemNotification(
            $validatedData['title'],
            $validatedData['message'],
            $validatedData['channels'],
            $validatedData['action_url'] ?? null,
            $validatedData['action_text'] ?? null,
            $validatedData['image'] ?? null
        );
        
        $users = User::where('is_active', true)->get();
        Notification::send($users, $notification);
        
        return redirect()->back()->with('success', 'Notificação enviada para ' . $users->count() . ' usuários.');
    }
} 