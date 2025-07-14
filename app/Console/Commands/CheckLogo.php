<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LogoResizeService;
use App\Models\Setting;

class CheckLogo extends Command
{
    protected $signature = 'logo:check';
    protected $description = 'Verifica o status do logotipo e suas versões redimensionadas';

    public function handle()
    {
        $this->info("🔍 Verificação do Sistema de Logotipo");
        
        // 1. Verificar configuração
        $siteLogo = Setting::get('site_logo', null);
        
        if (!$siteLogo) {
            $this->error("❌ Nenhum logotipo configurado");
            return 1;
        }
        
        $this->info("\n📊 Logotipo Configurado:");
        $this->line("  Caminho: {$siteLogo}");
        
        // 2. Verificar arquivo original
        $originalPath = public_path($siteLogo);
        if (!file_exists($originalPath)) {
            $this->error("❌ Arquivo original não encontrado: {$originalPath}");
            return 1;
        }
        
        $originalInfo = getimagesize($originalPath);
        $this->line("  Dimensões: {$originalInfo[0]}x{$originalInfo[1]} pixels");
        $this->line("  Tamanho: " . $this->formatBytes(filesize($originalPath)));
        $this->line("  URL: " . asset($siteLogo));
        
        // 3. Verificar versões redimensionadas
        $this->info("\n🔄 Versões Redimensionadas:");
        
        $versions = [
            'header' => LogoResizeService::getHeaderLogo(),
            'sidebar' => LogoResizeService::getSidebarLogo()
        ];
        
        foreach ($versions as $type => $path) {
            if ($path && file_exists(public_path($path))) {
                $info = getimagesize(public_path($path));
                $size = filesize(public_path($path));
                $this->info("  ✅ {$type}: {$info[0]}x{$info[1]} - " . $this->formatBytes($size));
                $this->line("     URL: " . asset($path));
            } else {
                $this->warn("  ⚠️  {$type}: Não encontrada");
                $this->line("     💡 Execute: php artisan logo:resize");
            }
        }
        
        // 4. Verificar favicon
        $pathInfo = pathinfo($siteLogo);
        $faviconPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-favicon-32x32.' . $pathInfo['extension'];
        
        if (file_exists(public_path($faviconPath))) {
            $faviconInfo = getimagesize(public_path($faviconPath));
            $faviconSize = filesize(public_path($faviconPath));
            $this->info("  ✅ favicon: {$faviconInfo[0]}x{$faviconInfo[1]} - " . $this->formatBytes($faviconSize));
            $this->line("     URL: " . asset($faviconPath));
        } else {
            $this->warn("  ⚠️  favicon: Não encontrada");
        }
        
        // 5. Verificar se precisa redimensionar
        $needsResize = $originalInfo[0] > LogoResizeService::HEADER_MAX_WIDTH || 
                      $originalInfo[1] > LogoResizeService::HEADER_MAX_HEIGHT;
        
        $this->info("\n📏 Análise de Tamanho:");
        
        if ($needsResize) {
            $this->warn("  ⚠️  Logotipo original é muito grande para header");
            $this->line("     Recomendado: máximo " . LogoResizeService::HEADER_MAX_WIDTH . "x" . LogoResizeService::HEADER_MAX_HEIGHT);
            $this->line("     Atual: {$originalInfo[0]}x{$originalInfo[1]}");
        } else {
            $this->info("  ✅ Logotipo original tem tamanho adequado");
        }
        
        // 6. Testar URLs
        $this->info("\n🌐 Teste de Conectividade:");
        
        $urlsToTest = [
            'original' => asset($siteLogo)
        ];
        
        foreach ($versions as $type => $path) {
            if ($path) {
                $urlsToTest[$type] = asset($path);
            }
        }
        
        if (file_exists(public_path($faviconPath))) {
            $urlsToTest['favicon'] = asset($faviconPath);
        }
        
        foreach ($urlsToTest as $type => $url) {
            $headers = @get_headers($url, 1);
            if ($headers && strpos($headers[0], '200') !== false) {
                $this->info("  ✅ {$type}: Acessível");
            } else {
                $this->error("  ❌ {$type}: Não acessível - {$url}");
            }
        }
        
        // 7. Verificar componente
        $this->info("\n🧩 Componente Application Logo:");
        $componentPath = resource_path('views/components/application-logo.blade.php');
        
        if (file_exists($componentPath)) {
            $content = file_get_contents($componentPath);
            
            if (strpos($content, 'LogoResizeService') !== false) {
                $this->info("  ✅ Componente usa LogoResizeService");
            } else {
                $this->warn("  ⚠️  Componente pode não estar usando redimensionamento");
            }
            
            if (strpos($content, 'application-logo') !== false) {
                $this->info("  ✅ Componente usa classes CSS específicas");
            } else {
                $this->warn("  ⚠️  Componente pode não estar usando classes CSS");
            }
        } else {
            $this->error("  ❌ Componente não encontrado");
        }
        
        // 8. Recomendações
        $this->info("\n💡 Recomendações:");
        
        $hasIssues = false;
        
        if ($needsResize && !$versions['header']) {
            $this->warn("  • Execute: php artisan logo:resize");
            $hasIssues = true;
        }
        
        if (!file_exists(public_path($faviconPath))) {
            $this->warn("  • Crie favicon: php artisan logo:resize");
            $hasIssues = true;
        }
        
        foreach ($urlsToTest as $type => $url) {
            $headers = @get_headers($url, 1);
            if (!$headers || strpos($headers[0], '200') === false) {
                $this->warn("  • Verifique conectividade para {$type}");
                $hasIssues = true;
            }
        }
        
        if (!$hasIssues) {
            $this->info("  ✅ Tudo está funcionando corretamente!");
        }
        
        $this->info("  • Limpe cache do navegador se necessário");
        $this->info("  • Teste em modo incógnito");
        
        return 0;
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
