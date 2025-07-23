<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Asset URL Configuration
    |--------------------------------------------------------------------------
    |
    | This value is the URL used to access your application's assets.
    | When using HTTPS, all assets should also be served over HTTPS.
    |
    */

    'url' => env('ASSET_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Force HTTPS for Assets
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, all asset URLs will be generated using
    | the HTTPS protocol, regardless of the current request protocol.
    |
    */

    'force_https' => env('FORCE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Asset Versioning
    |--------------------------------------------------------------------------
    |
    | This option controls whether asset URLs should include version
    | parameters to help with cache busting.
    |
    */

    'versioning' => env('ASSET_VERSIONING', true),
];
