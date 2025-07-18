<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LogoResizeService;
use App\Models\Setting;

class ResizeLogo extends Command
{
    protected $signature = 'logo:resize {--cleanup : Limpar versões redimensionadas antigas} {--force : Forçar recriação mesmo se já existir}';
    protected $description = 'Redimensiona o logotipo da plataforma para tamanhos otimizados';

    public function handle()
    {
        $cleanup = $this->option('cleanup');
        $force = $this->option('force');
        
        $this->info("🖼️  Redimensionamento de Logotipo");
        
        // Limpar versões antigas se solicitado
        if ($cleanup) {
            $this->info("\n🧹 Limpando versões redimensionadas antigas...");
            $cleaned = LogoResizeService::cleanupResizedVersions();
            $this->line("  Arquivos removidos: {$cleaned}");
        }
        
        // Verificar se há logotipo configurado
        $siteLogo = Setting::get('site_logo', null);
        
        if (!$siteLogo) {
            $this->error("❌ Nenhum logotipo configurado na plataforma");
            $this->line("💡 Configure um logotipo em /settings/appearance");
            return 1;
        }
        
        $this->info("\n📊 Logotipo atual: {$siteLogo}");
        
        // Verificar se arquivo existe
        $logoPath = public_path($siteLogo);
        if (!file_exists($logoPath)) {
            $this->error("❌ Arquivo de logotipo não encontrado: {$logoPath}");
            return 1;
        }
        
        // Obter informações da imagem original
        $imageInfo = getimagesize($logoPath);
        if (!$imageInfo) {
            $this->error("❌ Não foi possível obter informações da imagem");
            return 1;
        }
        
        $this->line("  Dimensões originais: {$imageInfo[0]}x{$imageInfo[1]} pixels");
        $this->line("  Tamanho: " . $this->formatBytes(filesize($logoPath)));
        $this->line("  Tipo: {$imageInfo['mime']}");
        
        // Verificar se precisa redimensionar
        $needsResize = $imageInfo[0] > LogoResizeService::HEADER_MAX_WIDTH || 
                      $imageInfo[1] > LogoResizeService::HEADER_MAX_HEIGHT;
        
        if (!$needsResize && !$force) {
            $this->info("\n✅ O logotipo já está em um tamanho adequado!");
            $this->line("💡 Use --force para recriar as versões redimensionadas");
            return 0;
        }
        
        $this->info("\n🔄 Criando versões redimensionadas...");
        
        $results = [];
        
        // 1. Versão para header
        $this->line("  📱 Criando versão para header...");
        $headerLogo = LogoResizeService::resizeForHeader($siteLogo);
        if ($headerLogo) {
            $headerInfo = getimagesize(public_path($headerLogo));
            $results['header'] = [
                'path' => $headerLogo,
                'dimensions' => "{$headerInfo[0]}x{$headerInfo[1]}",
                'size' => filesize(public_path($headerLogo))
            ];
            $this->info("    ✅ Header: {$headerInfo[0]}x{$headerInfo[1]} - " . $this->formatBytes($results['header']['size']));
        } else {
            $this->error("    ❌ Falha ao criar versão para header");
        }
        
        // 2. Versão para sidebar
        $this->line("  📋 Criando versão para sidebar...");
        $sidebarLogo = LogoResizeService::resizeForSidebar($siteLogo);
        if ($sidebarLogo) {
            $sidebarInfo = getimagesize(public_path($sidebarLogo));
            $results['sidebar'] = [
                'path' => $sidebarLogo,
                'dimensions' => "{$sidebarInfo[0]}x{$sidebarInfo[1]}",
                'size' => filesize(public_path($sidebarLogo))
            ];
            $this->info("    ✅ Sidebar: {$sidebarInfo[0]}x{$sidebarInfo[1]} - " . $this->formatBytes($results['sidebar']['size']));
        } else {
            $this->error("    ❌ Falha ao criar versão para sidebar");
        }
        
        // 3. Favicon
        $this->line("  🌐 Criando favicon...");
        $favicon = LogoResizeService::createFavicon($siteLogo);
        if ($favicon) {
            $faviconInfo = getimagesize(public_path($favicon));
            $results['favicon'] = [
                'path' => $favicon,
                'dimensions' => "{$faviconInfo[0]}x{$faviconInfo[1]}",
                'size' => filesize(public_path($favicon))
            ];
            $this->info("    ✅ Favicon: {$faviconInfo[0]}x{$faviconInfo[1]} - " . $this->formatBytes($results['favicon']['size']));
        } else {
            $this->error("    ❌ Falha ao criar favicon");
        }
        
        // Resumo
        $this->info("\n📊 Resumo:");
        $this->line("  Original: {$imageInfo[0]}x{$imageInfo[1]} - " . $this->formatBytes(filesize($logoPath)));
        
        $totalSaved = filesize($logoPath);
        foreach ($results as $type => $result) {
            $this->line("  " . ucfirst($type) . ": {$result['dimensions']} - " . $this->formatBytes($result['size']));
            $totalSaved -= $result['size'];
        }
        
        if ($totalSaved > 0) {
            $this->info("  💾 Economia total: " . $this->formatBytes($totalSaved));
        }
        
        // URLs de teste
        $this->info("\n🔗 URLs para teste:");
        foreach ($results as $type => $result) {
            $url = asset($result['path']);
            $this->line("  {$type}: {$url}");
        }
        
        $this->info("\n🎉 Redimensionamento concluído!");
        $this->line("💡 O componente application-logo agora usará automaticamente a versão apropriada");
        
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
