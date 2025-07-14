@php
    use App\Services\LogoResizeService;

    // Determinar contexto baseado nas classes
    $classes = $attributes->get('class', '');
    $context = 'header'; // padrão

    if (strpos($classes, 'sidebar') !== false || strpos($classes, 'w-6') !== false || strpos($classes, 'h-6') !== false) {
        $context = 'sidebar';
    }

    // Obter logotipo redimensionado apropriado
    $logoPath = null;
    if ($context === 'sidebar') {
        $logoPath = LogoResizeService::getSidebarLogo();
    } else {
        $logoPath = LogoResizeService::getHeaderLogo();
    }

    // Fallback para logotipo original se redimensionamento falhar
    if (!$logoPath) {
        $logoPath = \App\Models\Setting::get('site_logo', null);
    }

    $defaultLogo = 'assets/svg/svg_7fca9c99d8d71bc9eb4587a70a3a24a5.svg';
    $src = $logoPath ? asset($logoPath) . '?v=' . time() : asset($defaultLogo);

    // Extrair apenas classes de tamanho e posicionamento, removendo classes de cor
    $allowedClasses = [];

    // Classes permitidas (tamanho, posicionamento, etc.)
    $allowedPatterns = [
        '/h-\d+/', '/w-\d+/', '/w-auto/', '/h-auto/',
        '/max-w-\w+/', '/max-h-\w+/', '/min-w-\w+/', '/min-h-\w+/',
        '/block/', '/inline/', '/inline-block/', '/flex/', '/inline-flex/',
        '/object-\w+/', '/rounded\w*/', '/shadow\w*/', '/border\w*/',
        '/m-\d+/', '/mt-\d+/', '/mr-\d+/', '/mb-\d+/', '/ml-\d+/',
        '/p-\d+/', '/pt-\d+/', '/pr-\d+/', '/pb-\d+/', '/pl-\d+/',
        '/mx-\w+/', '/my-\w+/', '/px-\w+/', '/py-\w+/'
    ];

    foreach (explode(' ', $classes) as $class) {
        foreach ($allowedPatterns as $pattern) {
            if (preg_match($pattern, $class)) {
                $allowedClasses[] = $class;
                break;
            }
        }
    }

    $finalClasses = implode(' ', $allowedClasses);

    // Adicionar classes específicas do contexto se não houver classes de tamanho
    if ($context === 'header' && !preg_match('/[hw]-\d+/', $finalClasses)) {
        $finalClasses .= ' h-8 w-auto max-w-32';
    } elseif ($context === 'sidebar' && !preg_match('/[hw]-\d+/', $finalClasses)) {
        $finalClasses .= ' h-6 w-auto max-w-20';
    }
@endphp

@if($logoPath)
    <img src="{{ $src }}"
         alt="{{ config('app.name') }}"
         class="application-logo {{ $context }} {{ $finalClasses }}"
         style="display: block; max-width: 100%; height: auto; object-fit: contain;"
         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" />
    <div style="display: none;" class="logo-container">
        <img src="{{ asset($defaultLogo) }}"
             alt="{{ config('app.name') }}"
             class="application-logo {{ $context }} {{ $finalClasses }}" />
    </div>
@else
    <img src="{{ asset($defaultLogo) }}"
         alt="{{ config('app.name') }}"
         class="application-logo {{ $context }} {{ $finalClasses }}" />
@endif