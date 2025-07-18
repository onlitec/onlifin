<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;

class FixLogo extends Command
{
    protected $signature = 'fix:logo {--force : Forçar correções sem confirmação}';
    protected $description = 'Corrige problemas comuns com o logotipo da plataforma';

    public function handle()
    {
        $force = $this->option('force');
        
        $this->info("🔧 Correção de Problemas do Logotipo");
        
        $issues = [];
        $fixes = [];
        
        // 1. Verificar link simbólico
        $this->info("\n1️⃣ Verificando link simbólico do storage...");
        $storageLink = public_path('storage');
        $expectedTarget = storage_path('app/public');
        
        if (!is_link($storageLink)) {
            $issues[] = "Link simbólico do storage não existe";
            $fixes[] = function() {
                $this->line("  🔧 Criando link simbólico...");
                $this->call('storage:link');
                return "Link simbólico criado";
            };
        } else {
            $currentTarget = readlink($storageLink);
            if ($currentTarget !== $expectedTarget) {
                $issues[] = "Link simbólico aponta para local incorreto: {$currentTarget}";
                $fixes[] = function() use ($storageLink) {
                    $this->line("  🔧 Corrigindo link simbólico...");
                    unlink($storageLink);
                    $this->call('storage:link');
                    return "Link simbólico corrigido";
                };
            } else {
                $this->info("  ✅ Link simbólico está correto");
            }
        }
        
        // 2. Verificar configuração de logotipo
        $this->info("\n2️⃣ Verificando configuração do logotipo...");
        $siteLogo = Setting::get('site_logo', null);
        
        if (!$siteLogo) {
            $issues[] = "Nenhum logotipo configurado";
            $fixes[] = function() {
                $this->line("  🔧 Buscando logotipo disponível...");
                $logoDir = storage_path('app/public/site-logos');
                
                if (is_dir($logoDir)) {
                    $files = scandir($logoDir);
                    $logoFiles = array_filter($files, function($file) {
                        return !in_array($file, ['.', '..']) && preg_match('/\.(png|jpg|jpeg)$/i', $file);
                    });
                    
                    if (!empty($logoFiles)) {
                        $latestLogo = end($logoFiles);
                        Setting::set('site_logo', 'storage/site-logos/' . $latestLogo);
                        return "Logotipo configurado automaticamente: {$latestLogo}";
                    }
                }
                
                return "Nenhum logotipo encontrado para configurar automaticamente";
            };
        } else {
            $this->info("  ✅ Logotipo configurado: {$siteLogo}");
            
            // Verificar se arquivo existe
            $logoPath = public_path($siteLogo);
            if (!file_exists($logoPath)) {
                $issues[] = "Arquivo de logotipo não existe: {$logoPath}";
                $fixes[] = function() use ($siteLogo) {
                    $this->line("  🔧 Buscando logotipo alternativo...");
                    $logoDir = storage_path('app/public/site-logos');
                    
                    if (is_dir($logoDir)) {
                        $files = scandir($logoDir);
                        $logoFiles = array_filter($files, function($file) {
                            return !in_array($file, ['.', '..']) && preg_match('/\.(png|jpg|jpeg)$/i', $file);
                        });
                        
                        if (!empty($logoFiles)) {
                            $latestLogo = end($logoFiles);
                            Setting::set('site_logo', 'storage/site-logos/' . $latestLogo);
                            return "Logotipo atualizado para: {$latestLogo}";
                        }
                    }
                    
                    Setting::set('site_logo', null);
                    return "Configuração de logotipo removida (arquivo não encontrado)";
                };
            }
        }
        
        // 3. Verificar permissões
        $this->info("\n3️⃣ Verificando permissões...");
        $logoDir = storage_path('app/public/site-logos');
        
        if (is_dir($logoDir)) {
            $perms = fileperms($logoDir);
            $octal = substr(sprintf('%o', $perms), -3);
            
            if ($octal !== '755' && $octal !== '775') {
                $issues[] = "Permissões incorretas no diretório de logotipos: {$octal}";
                $fixes[] = function() use ($logoDir) {
                    $this->line("  🔧 Corrigindo permissões do diretório...");
                    chmod($logoDir, 0775);
                    return "Permissões do diretório corrigidas";
                };
            } else {
                $this->info("  ✅ Permissões do diretório estão corretas");
            }
            
            // Verificar permissões dos arquivos
            $files = glob($logoDir . '/*.{png,jpg,jpeg}', GLOB_BRACE);
            foreach ($files as $file) {
                $perms = fileperms($file);
                $octal = substr(sprintf('%o', $perms), -3);
                
                if ($octal !== '644' && $octal !== '664') {
                    $issues[] = "Permissões incorretas no arquivo: " . basename($file) . " ({$octal})";
                    $fixes[] = function() use ($file) {
                        $this->line("  🔧 Corrigindo permissões do arquivo: " . basename($file));
                        chmod($file, 0644);
                        return "Permissões do arquivo corrigidas: " . basename($file);
                    };
                }
            }
        }
        
        // 4. Verificar URL da aplicação
        $this->info("\n4️⃣ Verificando URL da aplicação...");
        $appUrl = config('app.url');
        
        if (strpos($appUrl, 'https://onlifin.onlitec.com.br') !== false) {
            $issues[] = "URL da aplicação está configurada para produção: {$appUrl}";
            $fixes[] = function() {
                $this->line("  🔧 Corrigindo URL da aplicação...");
                
                // Atualizar .env
                $envPath = base_path('.env');
                $envContent = file_get_contents($envPath);
                $envContent = preg_replace('/APP_URL=.*/', 'APP_URL=http://172.20.120.180', $envContent);
                file_put_contents($envPath, $envContent);
                
                // Limpar cache
                $this->call('config:clear');
                
                return "URL da aplicação corrigida para desenvolvimento";
            };
        } else {
            $this->info("  ✅ URL da aplicação está correta: {$appUrl}");
        }
        
        // Resumo dos problemas
        if (empty($issues)) {
            $this->info("\n🎉 Nenhum problema encontrado! O logotipo deve estar funcionando corretamente.");
            return 0;
        }
        
        $this->warn("\n⚠️  Problemas encontrados:");
        foreach ($issues as $i => $issue) {
            $this->line("  " . ($i + 1) . ". {$issue}");
        }
        
        // Aplicar correções
        if (!$force && !$this->confirm("\nDeseja aplicar as correções automaticamente?")) {
            $this->info("Correções canceladas pelo usuário.");
            return 0;
        }
        
        $this->info("\n🚀 Aplicando correções...");
        
        foreach ($fixes as $i => $fix) {
            try {
                $result = $fix();
                $this->info("  ✅ " . ($i + 1) . ". {$result}");
            } catch (\Exception $e) {
                $this->error("  ❌ " . ($i + 1) . ". Erro: " . $e->getMessage());
            }
        }
        
        $this->info("\n🎯 Correções concluídas!");
        $this->line("💡 Recomendações finais:");
        $this->line("  • Limpe o cache do navegador (Ctrl+F5)");
        $this->line("  • Teste em modo incógnito");
        $this->line("  • Execute: php artisan debug:logo para verificar");
        
        return 0;
    }
}
