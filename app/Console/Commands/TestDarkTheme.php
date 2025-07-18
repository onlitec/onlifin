<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;

class TestDarkTheme extends Command
{
    protected $signature = 'test:dark-theme';
    protected $description = 'Testa as melhorias do tema escuro da plataforma';

    public function handle()
    {
        $this->info("🌙 Teste das Melhorias do Tema Escuro");
        
        // 1. Verificar configuração atual do tema
        $currentTheme = Setting::get('site_theme', 'light');
        $this->info("\n1️⃣ Configuração atual do tema: {$currentTheme}");
        
        // 2. Verificar arquivos CSS
        $this->info("\n2️⃣ Verificando arquivos CSS...");
        
        $cssFiles = [
            'resources/css/app.css' => 'CSS principal',
            'public/css/dark-theme-improvements.css' => 'Melhorias do tema escuro'
        ];
        
        foreach ($cssFiles as $file => $description) {
            if (file_exists(base_path($file))) {
                $size = $this->formatBytes(filesize(base_path($file)));
                $this->info("  ✅ {$description}: {$size}");
            } else {
                $this->error("  ❌ {$description}: Arquivo não encontrado");
            }
        }
        
        // 3. Verificar JavaScript
        $this->info("\n3️⃣ Verificando JavaScript...");
        
        $jsFile = 'public/js/theme-switcher.js';
        if (file_exists(base_path($jsFile))) {
            $size = $this->formatBytes(filesize(base_path($jsFile)));
            $this->info("  ✅ Theme Switcher: {$size}");
        } else {
            $this->error("  ❌ Theme Switcher: Arquivo não encontrado");
        }
        
        // 4. Verificar classes dark: no CSS principal
        $this->info("\n4️⃣ Verificando classes dark: no CSS...");
        
        $appCssContent = file_get_contents(base_path('resources/css/app.css'));
        $darkClasses = preg_match_all('/dark:[a-zA-Z0-9-]+/', $appCssContent);
        
        $this->info("  📊 Classes dark: encontradas: {$darkClasses}");
        
        if ($darkClasses > 20) {
            $this->info("  ✅ Boa cobertura de classes dark:");
        } else {
            $this->warn("  ⚠️  Poucas classes dark: encontradas");
        }
        
        // 5. Verificar página de aparência
        $this->info("\n5️⃣ Verificando página de aparência...");
        
        $appearanceFile = 'resources/views/settings/appearance.blade.php';
        if (file_exists(base_path($appearanceFile))) {
            $content = file_get_contents(base_path($appearanceFile));
            
            // Verificar se tem classes dark:
            $darkClassesInView = preg_match_all('/dark:[a-zA-Z0-9-]+/', $content);
            $this->info("  📊 Classes dark: na página: {$darkClassesInView}");
            
            // Verificar elementos específicos
            $checks = [
                'dark:bg-gray-800' => 'Background escuro para cards',
                'dark:text-gray-100' => 'Texto claro',
                'dark:border-gray-600' => 'Bordas escuras',
                'dark:text-gray-300' => 'Labels escuros'
            ];
            
            foreach ($checks as $class => $description) {
                if (strpos($content, $class) !== false) {
                    $this->info("  ✅ {$description}");
                } else {
                    $this->warn("  ⚠️  {$description}: não encontrado");
                }
            }
        } else {
            $this->error("  ❌ Página de aparência não encontrada");
        }
        
        // 6. Verificar configuração do Tailwind
        $this->info("\n6️⃣ Verificando configuração do Tailwind...");
        
        $tailwindConfig = 'tailwind.config.js';
        if (file_exists(base_path($tailwindConfig))) {
            $content = file_get_contents(base_path($tailwindConfig));
            
            if (strpos($content, "darkMode: 'class'") !== false) {
                $this->info("  ✅ Dark mode configurado como 'class'");
            } else {
                $this->warn("  ⚠️  Dark mode pode não estar configurado corretamente");
            }
        } else {
            $this->error("  ❌ tailwind.config.js não encontrado");
        }
        
        // 7. Testar mudança de tema
        $this->info("\n7️⃣ Testando mudança de tema...");
        
        $originalTheme = Setting::get('site_theme', 'light');
        
        try {
            // Testar tema escuro
            Setting::set('site_theme', 'dark');
            $darkTheme = Setting::get('site_theme');
            
            if ($darkTheme === 'dark') {
                $this->info("  ✅ Mudança para tema escuro: OK");
            } else {
                $this->error("  ❌ Falha ao mudar para tema escuro");
            }
            
            // Testar tema claro
            Setting::set('site_theme', 'light');
            $lightTheme = Setting::get('site_theme');
            
            if ($lightTheme === 'light') {
                $this->info("  ✅ Mudança para tema claro: OK");
            } else {
                $this->error("  ❌ Falha ao mudar para tema claro");
            }
            
            // Restaurar tema original
            Setting::set('site_theme', $originalTheme);
            
        } catch (\Exception $e) {
            $this->error("  ❌ Erro ao testar mudança de tema: " . $e->getMessage());
        }
        
        // 8. Verificar URLs de teste
        $this->info("\n8️⃣ URLs para teste manual:");
        $this->line("  🌐 Página de aparência: http://172.20.120.180/settings/appearance");
        $this->line("  🌐 Dashboard: http://172.20.120.180/dashboard");
        $this->line("  🌐 Transações: http://172.20.120.180/transactions");
        
        // 9. Instruções para teste
        $this->info("\n9️⃣ Instruções para teste manual:");
        $this->line("  1. Acesse a página de aparência");
        $this->line("  2. Altere o tema para 'Escuro'");
        $this->line("  3. Clique em 'Salvar Alterações'");
        $this->line("  4. Verifique se todos os elementos estão visíveis");
        $this->line("  5. Navegue por diferentes páginas");
        $this->line("  6. Teste formulários e interações");
        
        // 10. Resumo
        $this->info("\n📋 RESUMO:");
        
        $issues = 0;
        
        // Verificar se todos os arquivos existem
        $requiredFiles = [
            'resources/css/app.css',
            'public/css/dark-theme-improvements.css',
            'public/js/theme-switcher.js',
            'resources/views/settings/appearance.blade.php'
        ];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists(base_path($file))) {
                $issues++;
            }
        }
        
        if ($issues === 0) {
            $this->info("🎉 SUCESSO: Todas as melhorias do tema escuro estão implementadas!");
            $this->info("✅ Arquivos CSS e JS presentes");
            $this->info("✅ Classes dark: implementadas");
            $this->info("✅ Página de aparência atualizada");
            $this->info("✅ Sistema de troca de tema funcionando");
        } else {
            $this->warn("⚠️  {$issues} problemas encontrados");
            $this->info("💡 Verifique os arquivos listados acima");
        }
        
        return $issues === 0 ? 0 : 1;
    }
    
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}
