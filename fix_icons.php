<?php

function fixIcons($file) {
    $content = file_get_contents($file);
    
    // Encontra todos os ícones do Remix Icons
    preg_match_all('/<i class="ri-[^>]*">/', $content, $matches);
    
    foreach ($matches[0] as $icon) {
        // Verifica se o ícone está usando a classe correta
        if (preg_match('/class="ri-([^"]+)"/', $icon, $classMatches)) {
            $className = $classMatches[1];
            
            // Verifica se o ícone existe no CSS
            $cssFile = __DIR__ . '/assets/css/remixicon.css';
            if (file_exists($cssFile)) {
                $cssContent = file_get_contents($cssFile);
                if (!preg_match('/\.' . preg_quote($className, '/') . '/', $cssContent)) {
                    // Se o ícone não existe, adiciona a classe de fallback
                    $newIcon = str_replace('class="ri-', 'class="ri-question-line ri-', $icon);
                    $content = str_replace($icon, $newIcon, $content);
                }
            }
        }
    }
    
    // Salva o arquivo atualizado
    file_put_contents($file, $content);
}

// Lista de arquivos que podem conter ícones
$files = array(
    'resources/views/layouts/app.blade.php',
    'resources/views/layouts/nav.blade.php',
    'resources/views/layouts/navigation.blade.php',
    'resources/views/notifications/index.blade.php',
    'resources/views/notifications/due-date/settings.blade.php',
    'resources/views/notifications/settings.blade.php',
    'resources/views/settings/notifications/index.blade.php',
    'resources/views/settings/notifications/email.blade.php',
    'resources/views/settings/notifications/whatsapp.blade.php',
    'resources/views/settings/notifications/push.blade.php'
);

foreach ($files as $file) {
    if (file_exists($file)) {
        fixIcons($file);
    }
}

?>
