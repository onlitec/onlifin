<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
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
        try {
            // Garanta que o model Setting exista e o método get() esteja implementado corretamente.
            // Exemplo: App\Models\Setting::get('key', 'default_value');
            $siteTitle = \App\Models\Setting::get('site_title', config('app.name', 'Onlifin'));
            $siteFavicon = \App\Models\Setting::get('site_favicon', 'favicon.ico');
            $siteTheme = \App\Models\Setting::get('site_theme', 'light'); // 'light' ou 'dark'
            $rootFontSize = \App\Models\Setting::get('root_font_size', '16'); // valor numérico em px
            $cardFontSize = \App\Models\Setting::get('card_font_size', '2xl'); // ex: 'xl', '2xl', '3xl' conforme Tailwind

            View::share('siteTitle', $siteTitle);
            View::share('siteFavicon', $siteFavicon);
            View::share('siteTheme', $siteTheme);
            View::share('rootFontSize', $rootFontSize); // Será usado como string no HTML, não precisa concatenar 'px' aqui
            View::share('cardFontSize', $cardFontSize); // Passar como está, a view decidirá como usar (ex: text-{{ $cardFontSize }})

        } catch (\Illuminate\Database\QueryException $e) {
            Log::warning('AppServiceProvider: Não foi possível conectar ao banco de dados ou a tabela de configurações não foi encontrada durante o boot: ' . $e->getMessage());
            // Compartilhar valores padrão para evitar que as views quebrem
            View::share('siteTitle', config('app.name', 'Onlifin'));
            View::share('siteFavicon', 'favicon.ico');
            View::share('siteTheme', 'light');
            View::share('rootFontSize', '16');
            View::share('cardFontSize', '2xl');
        } catch (\Exception $e) {
            // Captura outras exceções genéricas que podem ocorrer ao buscar settings
            Log::error('AppServiceProvider: Erro ao buscar configurações do site: ' . $e->getMessage());
            // Compartilhar valores padrão para evitar que as views quebrem
            View::share('siteTitle', config('app.name', 'Onlifin'));
            View::share('siteFavicon', 'favicon.ico');
            View::share('siteTheme', 'light');
            View::share('rootFontSize', '16');
            View::share('cardFontSize', '2xl');
        }
    }
}
