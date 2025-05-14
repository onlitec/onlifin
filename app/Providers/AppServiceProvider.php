<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Account;
use App\Observers\UserObserver;
use App\Observers\TransactionObserver;
use App\Observers\AccountObserver;
use App\Livewire\Transactions\Income;
use App\Livewire\Transactions\Expenses;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configurar codificação UTF-8 para toda a aplicação
        mb_internal_encoding('UTF-8');
        
        // Registrar provider de configuração de IA
        $this->app->singleton('ai.config', function () {
            return new \App\Services\AIConfigService();
        });
        
        // Middlewares
        $this->app['router']->pushMiddlewareToGroup('web', \App\Http\Middleware\HandleUTF8Encoding::class);

        Blade::component('components.application-logo', 'application-logo');
        User::observe(UserObserver::class);
        Transaction::observe(TransactionObserver::class);
        Account::observe(AccountObserver::class);

        // Configurando o Carbon para português
        Carbon::setLocale('pt_BR');

        // Registrando componentes Livewire
        Livewire::component('transactions.income', Income::class);
        Livewire::component('transactions.expenses', Expenses::class);
        Livewire::component('partials.delete-transaction-button', \App\Livewire\Partials\DeleteTransactionButton::class);
        Livewire::component('settings.users.list-users', \App\Livewire\Settings\Users\ListUsers::class);
        Livewire::component('settings.users.create', \App\Livewire\Settings\Users\Create::class);
        Livewire::component('settings.users.edit', \App\Livewire\Settings\Users\Edit::class);
        Livewire::component('settings.users.delete', \App\Livewire\Settings\Users\Delete::class);
        Livewire::component('settings.users.create-user', \App\Livewire\Settings\Users\CreateUser::class);
        Livewire::component('settings.users.edit-user', \App\Livewire\Settings\Users\EditUser::class);
        Livewire::component('settings.roles.list-roles', \App\Livewire\Settings\Roles\ListRoles::class);
        Livewire::component('settings.roles.create', \App\Livewire\Settings\Roles\Create::class);
        Livewire::component('settings.roles.edit', \App\Livewire\Settings\Roles\Edit::class);
        Livewire::component('settings.roles.delete', \App\Livewire\Settings\Roles\Delete::class);
        Livewire::component('notification-settings-modal', \App\Livewire\NotificationSettingsModal::class);
        Livewire::component('wire-elements-modal', \LivewireUI\Modal\Modal::class);
        Livewire::component('settings.whatsapp-config', \App\Livewire\Settings\WhatsAppConfig::class);
        Livewire::component('settings.logs', \App\Livewire\Settings\Logs::class);

        // Registrar canal de WhatsApp
        \Illuminate\Support\Facades\Notification::extend('whatsapp', function ($app) {
            return new \App\Channels\WhatsAppChannel();
        });

        // Compartilhar título do site e favicon dinâmicos de configurações
        $siteTitle = \App\Models\Setting::get('site_title', config('app.name'));
        $siteFavicon = \App\Models\Setting::get('site_favicon', 'favicon.ico');
        \Illuminate\Support\Facades\View::share('siteTitle', $siteTitle);
        \Illuminate\Support\Facades\View::share('siteFavicon', $siteFavicon);
        // Compartilhar tema do site (claro/escuro)
        $siteTheme = \App\Models\Setting::get('site_theme', 'light');
        \Illuminate\Support\Facades\View::share('siteTheme', $siteTheme);
    }
}
