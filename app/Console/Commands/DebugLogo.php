<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;

class DebugLogo extends Command
{
    protected $signature = 'debug:logo';
    protected $description = 'Debug do sistema de logotipo da plataforma';

    public function handle()
    {
        $this->info("🔍 Debug do Sistema de Logotipo");
        
        // 1. Verificar configuração no banco
        $this->info("\n1️⃣ Configuração no Banco de Dados:");
        $siteLogo = Setting::get('site_logo', null);
        
        if ($siteLogo) {
            $this->line("  ✅ site_logo: {$siteLogo}");
        } else {
            $this->error("  ❌ site_logo: NULL ou não configurado");
        }
        
        // 2. Verificar arquivos físicos
        $this->info("\n2️⃣ Verificação de Arquivos:");
        
        if ($siteLogo) {
            $publicPath = public_path($siteLogo);
            $storageOriginal = storage_path('app/public/' . str_replace('storage/', '', $siteLogo));
            
            $this->line("  Caminho público: {$publicPath}");
            $this->line("  Caminho storage: {$storageOriginal}");
            
            if (file_exists($publicPath)) {
                $this->info("  ✅ Arquivo acessível via public: SIM");
                $this->line("  📊 Tamanho: " . $this->formatBytes(filesize($publicPath)));
                $this->line("  🔒 Permissões: " . substr(sprintf('%o', fileperms($publicPath)), -4));
            } else {
                $this->error("  ❌ Arquivo acessível via public: NÃO");
            }
            
            if (file_exists($storageOriginal)) {
                $this->info("  ✅ Arquivo original no storage: SIM");
                $this->line("  📊 Tamanho: " . $this->formatBytes(filesize($storageOriginal)));
                $this->line("  🔒 Permissões: " . substr(sprintf('%o', fileperms($storageOriginal)), -4));
            } else {
                $this->error("  ❌ Arquivo original no storage: NÃO");
            }
        }
        
        // 3. Verificar link simbólico
        $this->info("\n3️⃣ Link Simbólico do Storage:");
        $storageLink = public_path('storage');
        
        if (is_link($storageLink)) {
            $target = readlink($storageLink);
            $this->info("  ✅ Link existe: {$storageLink} -> {$target}");
            
            if (is_dir($target)) {
                $this->info("  ✅ Destino do link é válido");
            } else {
                $this->error("  ❌ Destino do link não existe ou não é um diretório");
            }
        } else {
            $this->error("  ❌ Link simbólico não existe");
            $this->warn("  💡 Execute: php artisan storage:link");
        }
        
        // 4. Testar URLs
        $this->info("\n4️⃣ Teste de URLs:");
        
        if ($siteLogo) {
            $url = asset($siteLogo);
            $this->line("  URL gerada: {$url}");
            
            // Testar acesso HTTP
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 10,
                        'method' => 'HEAD'
                    ]
                ]);
                
                $headers = @get_headers($url, 1, $context);
                
                if ($headers && strpos($headers[0], '200') !== false) {
                    $this->info("  ✅ URL acessível via HTTP: SIM");
                    if (isset($headers['Content-Type'])) {
                        $this->line("  📄 Content-Type: {$headers['Content-Type']}");
                    }
                    if (isset($headers['Content-Length'])) {
                        $this->line("  📊 Content-Length: " . $this->formatBytes($headers['Content-Length']));
                    }
                } else {
                    $this->error("  ❌ URL acessível via HTTP: NÃO");
                    if ($headers) {
                        $this->line("  📄 Status: {$headers[0]}");
                    }
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Erro ao testar URL: " . $e->getMessage());
            }
        }
        
        // 5. Verificar outros logotipos
        $this->info("\n5️⃣ Outros Logotipos Disponíveis:");
        $logoDir = storage_path('app/public/site-logos');
        
        if (is_dir($logoDir)) {
            $files = scandir($logoDir);
            $logoFiles = array_filter($files, function($file) {
                return !in_array($file, ['.', '..']) && preg_match('/\.(png|jpg|jpeg|svg)$/i', $file);
            });
            
            if (!empty($logoFiles)) {
                foreach ($logoFiles as $file) {
                    $filePath = $logoDir . '/' . $file;
                    $size = $this->formatBytes(filesize($filePath));
                    $isCurrent = ($siteLogo === 'storage/site-logos/' . $file) ? ' (ATUAL)' : '';
                    $this->line("  📄 {$file}: {$size}{$isCurrent}");
                }
            } else {
                $this->warn("  ⚠️  Nenhum arquivo de logotipo encontrado");
            }
        } else {
            $this->error("  ❌ Diretório de logotipos não existe");
        }
        
        // 6. Verificar componente application-logo
        $this->info("\n6️⃣ Componente Application Logo:");
        $componentPath = resource_path('views/components/application-logo.blade.php');
        
        if (file_exists($componentPath)) {
            $this->info("  ✅ Componente existe: {$componentPath}");
            $content = file_get_contents($componentPath);
            
            if (strpos($content, 'Setting::get') !== false) {
                $this->info("  ✅ Componente usa Setting::get para buscar logotipo");
            } else {
                $this->warn("  ⚠️  Componente pode não estar buscando configuração corretamente");
            }
        } else {
            $this->error("  ❌ Componente não encontrado");
        }
        
        // 7. Recomendações
        $this->info("\n💡 Recomendações:");
        
        if (!$siteLogo) {
            $this->warn("  • Configure um logotipo em /settings/appearance");
        }
        
        if ($siteLogo && !file_exists(public_path($siteLogo))) {
            $this->warn("  • Execute: php artisan storage:link");
        }
        
        $this->info("  • Limpe cache do navegador (Ctrl+F5)");
        $this->info("  • Verifique se não há CSS ocultando a imagem");
        $this->info("  • Teste em modo incógnito do navegador");
        
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
