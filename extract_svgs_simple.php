<?php

$files = array(
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
);

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Encontra todos os SVGs no arquivo
    preg_match_all('/<svg[^>]*>.*?<\/svg>/s', $content, $matches);
    
    if (!empty($matches[0])) {
        foreach ($matches[0] as $svg) {
            // Gera um hash único para o arquivo SVG
            $hash = md5($svg);
            $filename = "svg_{$hash}.svg";
            
            // Salva o SVG no diretório
            file_put_contents("public/assets/svg/{$filename}", $svg);
            
            // Substitui o SVG inline por uma tag img
            $replacement = "<img src=\"{{ asset('assets/svg/{$filename}') }}\" alt=\"\" class=\"\"/>";
            $content = str_replace($svg, $replacement, $content);
        }
        
        // Salva o arquivo atualizado
        file_put_contents($file, $content);
    }
}

?>
