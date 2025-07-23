<?php

return [
    'class_namespace' => 'App\\Livewire',
    'view_path' => resource_path('views/livewire'),
    'layout' => 'layouts.app',
    'asset_url' => env('ASSET_URL'),
    'app_url' => env('APP_URL'),
    'middleware_group' => 'web',
    'inject_assets' => true,
    'temporary_file_upload' => [
        'disk' => null,
        'rules' => null,
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
    ],
    'manifest_path' => null,
    'back_button_cache' => false,
    'render_on_redirect' => false,

    'components' => [
        'notification-settings' => App\Livewire\NotificationSettings::class,
        'settings.users.list-users' => App\Livewire\Settings\Users\ListUsers::class,
        'settings.users.create' => App\Livewire\Settings\Users\Create::class,
        'settings.users.edit' => App\Livewire\Settings\Users\Edit::class,
        'settings.users.delete' => App\Livewire\Settings\Users\Delete::class,
        'settings.roles.list-roles' => App\Livewire\Settings\Roles\ListRoles::class,
        'settings.roles.create' => App\Livewire\Settings\Roles\Create::class,
        'settings.roles.edit' => App\Livewire\Settings\Roles\Edit::class,
        'settings.roles.delete' => App\Livewire\Settings\Roles\Delete::class,
    ],
]; 