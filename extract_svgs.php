<?php

function extractSVGs($file) {
    $content = file_get_contents($file);
    preg_match_all('/<svg[^>]*>.*?<\/svg>/s', $content, $matches);
    
    foreach ($matches[0] as $svg) {
        // Remove as tags <svg> e </svg>
        $content = substr($svg, strpos($svg, '>') + 1);
        $content = substr($content, 0, strrpos($content, '<') - 1);
        
        // Gera um nome baseado no conteúdo do SVG
        $hash = md5($content);
        $filename = "svg_{$hash}.svg";
        
        // Salva o SVG
        $svgContent = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n" . $svg;
        file_put_contents("public/assets/svg/{$filename}", $svgContent);
        
        // Atualiza o arquivo original
        $replacement = "<img src=\"{{ asset('assets/svg/{$filename}') }}\" alt=\"\" class=\"";
        
        // Adiciona classes
        if (preg_match('/class="([^"]*)"/', $svg, $classMatches)) {
            $replacement .= $classMatches[1];
        }
        // Adiciona width
        if (preg_match('/width="([^"]*)"/', $svg, $widthMatches)) {
            $replacement .= " w-{$widthMatches[1]}";
        }
        // Adiciona height
        if (preg_match('/height="([^"]*)"/', $svg, $heightMatches)) {
            $replacement .= " h-{$heightMatches[1]}";
        }
        $replacement .= '" alt="">';
        
        $newContent = str_replace($svg, $replacement, $content);
        file_put_contents($file, $newContent);
    }
}

// Lista de arquivos que contêm SVGs
$files = [
    'resources/views/livewire/components/modal.blade.php',
    'resources/views/livewire/notification-settings-modal.blade.php',
    'resources/views/livewire/notification-settings.blade.php',
    'resources/views/livewire/auth/reset-password.blade.php',
    'resources/views/livewire/auth/register.blade.php',
    'resources/views/livewire/settings/push-config.blade.php',
    'resources/views/livewire/settings/system-settings.blade.php',
    'resources/views/livewire/settings/email-config.blade.php',
    'resources/views/livewire/settings/whatsapp-config.blade.php',
    'resources/views/components/application-logo.blade.php',
    'resources/views/layouts/navigation.blade.php',
    'resources/views/layouts/nav.blade.php',
    'resources/views/notifications/index.blade.php',
    'resources/views/notifications/due-date/settings.blade.php',
    'resources/views/notifications/settings.blade.php',
    'resources/views/settings/notifications/index.blade.php',
    'resources/views/settings/notifications/email.blade.php',
    'resources/views/settings/notifications/whatsapp.blade.php',
    'resources/views/settings/notifications/push.blade.php'
];

foreach ($files as $file) {
    extractSVGs($file);
}

?>
