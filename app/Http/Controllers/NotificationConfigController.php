<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\Channels\WhatsApp\WhatsAppProviderFactory;

class NotificationConfigController extends Controller
{
    /**
     * Exibir a página de configurações de notificação
     */
    public function index()
    {
        $user = auth()->user();
        return view('settings.notifications.index', compact('user'));
    }
    
    /**
     * Exibir a página de configurações de WhatsApp
     */
    public function whatsapp()
    {
        $providers = WhatsAppProviderFactory::getAvailableProviders();
        $currentProvider = config('notification-channels.whatsapp.default');
        
        return view('settings.notifications.whatsapp', [
            'providers' => $providers,
            'currentProvider' => $currentProvider,
        ]);
    }
    
    /**
     * Exibir a página de configurações de Email
     */
    public function email()
    {
        $mailConfig = [
            'driver' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];
        
        return view('settings.notifications.email', [
            'mailConfig' => $mailConfig,
        ]);
    }
    
    /**
     * Exibir a página de configurações de notificações Push
     */
    public function push()
    {
        $pushConfig = [
            'enabled' => config('notification-channels.push.enabled', false),
            'vapid_public_key' => config('notification-channels.push.vapid_public_key'),
            'vapid_private_key' => config('notification-channels.push.vapid_private_key'),
            'vapid_subject' => config('notification-channels.push.vapid_subject', 'mailto:notifications@onlifin.com'),
        ];
        
        return view('settings.notifications.push', [
            'pushConfig' => $pushConfig,
        ]);
    }
    
    /**
     * Exibir a página de modelos de notificação
     */
    public function templates()
    {
        return view('settings.notifications.templates');
    }
    
    /**
     * Atualizar configurações de notificações do usuário
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $user->update([
            'email_notifications' => $request->has('email_notifications'),
            'whatsapp_notifications' => $request->has('whatsapp_notifications'),
            'push_notifications' => $request->has('push_notifications'),
            'due_date_notifications' => $request->has('due_date_notifications'),
        ]);
        
        return back()->with('success', 'Configurações de notificações atualizadas com sucesso!');
    }
    
    /**
     * Enviar notificação de teste
     */
    public function sendTest(Request $request)
    {
        $user = Auth::user();
        $channels = $request->input('channels', []);
        
        if (empty($channels)) {
            return back()->with('error', 'Selecione pelo menos um canal de notificação para o teste.');
        }
        
        $user->notify(new \App\Notifications\TestNotification(
            $channels,
            'Teste de Configuração',
            'Esta é uma notificação de teste enviada pelo administrador em ' . now()->format('d/m/Y H:i:s')
        ));
        
        return back()->with('success', 'Notificação de teste enviada com sucesso!');
    }
}
