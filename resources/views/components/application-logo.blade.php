@php
    $logoPath = \App\Models\Setting::get('site_logo', null);
    $defaultLogo = 'assets/svg/svg_7fca9c99d8d71bc9eb4587a70a3a24a5.svg';
    $src = $logoPath ? asset($logoPath) . '?v=' . time() : asset($defaultLogo);
@endphp
<img src="{{ $src }}" alt="{{ config('app.name') }}" class="{{ $attributes->get('class') ?? '' }}"/> 