<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LogoResizeService
{
    // Dimensões ideais para diferentes contextos
    const HEADER_MAX_WIDTH = 120;
    const HEADER_MAX_HEIGHT = 40;
    const SIDEBAR_MAX_WIDTH = 80;
    const SIDEBAR_MAX_HEIGHT = 30;
    const FAVICON_SIZE = 32;
    
    /**
     * Redimensiona o logotipo para o tamanho ideal do header
     */
    public static function resizeForHeader(string $logoPath): ?string
    {
        return self::resizeImage($logoPath, self::HEADER_MAX_WIDTH, self::HEADER_MAX_HEIGHT, 'header');
    }
    
    /**
     * Redimensiona o logotipo para o tamanho ideal da sidebar
     */
    public static function resizeForSidebar(string $logoPath): ?string
    {
        return self::resizeImage($logoPath, self::SIDEBAR_MAX_WIDTH, self::SIDEBAR_MAX_HEIGHT, 'sidebar');
    }
    
    /**
     * Cria um favicon a partir do logotipo
     */
    public static function createFavicon(string $logoPath): ?string
    {
        return self::resizeImage($logoPath, self::FAVICON_SIZE, self::FAVICON_SIZE, 'favicon', true);
    }
    
    /**
     * Redimensiona uma imagem mantendo a proporção
     */
    private static function resizeImage(string $logoPath, int $maxWidth, int $maxHeight, string $suffix = '', bool $square = false): ?string
    {
        try {
            $fullPath = public_path($logoPath);
            
            if (!file_exists($fullPath)) {
                Log::error('Arquivo de logotipo não encontrado', ['path' => $fullPath]);
                return null;
            }
            
            // Obter informações da imagem
            $imageInfo = getimagesize($fullPath);
            if (!$imageInfo) {
                Log::error('Não foi possível obter informações da imagem', ['path' => $fullPath]);
                return null;
            }
            
            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            $mimeType = $imageInfo['mime'];
            
            // Verificar se já está no tamanho ideal
            if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
                Log::info('Imagem já está no tamanho ideal', [
                    'original' => "{$originalWidth}x{$originalHeight}",
                    'max' => "{$maxWidth}x{$maxHeight}"
                ]);
                return $logoPath;
            }
            
            // Calcular novas dimensões mantendo proporção
            if ($square) {
                $newWidth = $newHeight = min($maxWidth, $maxHeight);
            } else {
                $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
                $newWidth = (int) round($originalWidth * $ratio);
                $newHeight = (int) round($originalHeight * $ratio);
            }
            
            // Criar imagem de origem
            $sourceImage = self::createImageFromFile($fullPath, $mimeType);
            if (!$sourceImage) {
                Log::error('Não foi possível criar imagem de origem', ['mime' => $mimeType]);
                return null;
            }
            
            // Criar imagem de destino
            $destImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preservar transparência para PNG
            if ($mimeType === 'image/png') {
                imagealphablending($destImage, false);
                imagesavealpha($destImage, true);
                $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
                imagefill($destImage, 0, 0, $transparent);
            }
            
            // Redimensionar
            imagecopyresampled(
                $destImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $originalWidth, $originalHeight
            );
            
            // Gerar nome do arquivo redimensionado
            $pathInfo = pathinfo($logoPath);
            $newFileName = $pathInfo['filename'] . ($suffix ? "-{$suffix}" : '') . "-{$newWidth}x{$newHeight}." . $pathInfo['extension'];
            $newPath = $pathInfo['dirname'] . '/' . $newFileName;
            $newFullPath = public_path($newPath);
            
            // Salvar imagem redimensionada
            $saved = false;
            switch ($mimeType) {
                case 'image/jpeg':
                    $saved = imagejpeg($destImage, $newFullPath, 90);
                    break;
                case 'image/png':
                    $saved = imagepng($destImage, $newFullPath, 6);
                    break;
                case 'image/gif':
                    $saved = imagegif($destImage, $newFullPath);
                    break;
            }
            
            // Limpar memória
            imagedestroy($sourceImage);
            imagedestroy($destImage);
            
            if ($saved) {
                Log::info('Logotipo redimensionado com sucesso', [
                    'original' => "{$originalWidth}x{$originalHeight}",
                    'new' => "{$newWidth}x{$newHeight}",
                    'file' => $newFileName
                ]);
                
                return $newPath;
            } else {
                Log::error('Falha ao salvar imagem redimensionada');
                return null;
            }
            
        } catch (\Exception $e) {
            Log::error('Erro ao redimensionar logotipo', [
                'error' => $e->getMessage(),
                'path' => $logoPath
            ]);
            return null;
        }
    }
    
    /**
     * Cria uma imagem GD a partir de um arquivo
     */
    private static function createImageFromFile(string $filePath, string $mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($filePath);
            case 'image/png':
                return imagecreatefrompng($filePath);
            case 'image/gif':
                return imagecreatefromgif($filePath);
            default:
                return null;
        }
    }
    
    /**
     * Obtém o logotipo redimensionado para header ou cria se não existir
     */
    public static function getHeaderLogo(): ?string
    {
        $siteLogo = \App\Models\Setting::get('site_logo', null);
        
        if (!$siteLogo) {
            return null;
        }
        
        // Verificar se já existe versão redimensionada
        $pathInfo = pathinfo($siteLogo);
        $headerLogoPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-header-' . self::HEADER_MAX_WIDTH . 'x' . self::HEADER_MAX_HEIGHT . '.' . $pathInfo['extension'];
        
        if (file_exists(public_path($headerLogoPath))) {
            return $headerLogoPath;
        }
        
        // Criar versão redimensionada
        return self::resizeForHeader($siteLogo);
    }
    
    /**
     * Obtém o logotipo redimensionado para sidebar ou cria se não existir
     */
    public static function getSidebarLogo(): ?string
    {
        $siteLogo = \App\Models\Setting::get('site_logo', null);
        
        if (!$siteLogo) {
            return null;
        }
        
        // Verificar se já existe versão redimensionada
        $pathInfo = pathinfo($siteLogo);
        $sidebarLogoPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-sidebar-' . self::SIDEBAR_MAX_WIDTH . 'x' . self::SIDEBAR_MAX_HEIGHT . '.' . $pathInfo['extension'];
        
        if (file_exists(public_path($sidebarLogoPath))) {
            return $sidebarLogoPath;
        }
        
        // Criar versão redimensionada
        return self::resizeForSidebar($siteLogo);
    }
    
    /**
     * Limpa versões redimensionadas antigas
     */
    public static function cleanupResizedVersions(): int
    {
        $cleaned = 0;
        $logoDir = storage_path('app/public/site-logos');
        
        if (!is_dir($logoDir)) {
            return 0;
        }
        
        $files = glob($logoDir . '/*-{header,sidebar,favicon}-*', GLOB_BRACE);
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $cleaned++;
                Log::info('Versão redimensionada removida', ['file' => basename($file)]);
            }
        }
        
        return $cleaned;
    }
}
