<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use App\Models\User;
use App\Observers\UserObserver;
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
        Blade::component('components.application-logo', 'application-logo');
        User::observe(UserObserver::class);

        // Configurando o Carbon para português
        Carbon::setLocale('pt_BR');

        // Registrando componentes Livewire
        Livewire::component('transactions.income', Income::class);
        Livewire::component('transactions.expenses', Expenses::class);
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

        // Registrar canal de WhatsApp
        \Illuminate\Support\Facades\Notification::extend('whatsapp', function ($app) {
            return new \App\Channels\WhatsAppChannel();
        });
    }
}
