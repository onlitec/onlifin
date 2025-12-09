#!/usr/bin/env node

/**
 * Script para gerar ícones PWA para o OnliFin
 * 
 * Este script cria ícones SVG simples que podem ser usados como placeholder
 * até que ícones personalizados sejam criados.
 */

const fs = require('fs');
const path = require('path');

const iconsDir = path.join(__dirname, '../public/icons');

// Criar diretório se não existir
if (!fs.existsSync(iconsDir)) {
  fs.mkdirSync(iconsDir, { recursive: true });
  console.log('✅ Diretório /public/icons criado');
}

// Tamanhos de ícones necessários
const sizes = [72, 96, 128, 144, 152, 192, 384, 512];
const maskableSizes = [192, 512];
const shortcutSize = 96;

/**
 * Gera um SVG simples para o ícone
 */
function generateIconSVG(size, isMaskable = false, isShortcut = false, shortcutType = '') {
  const padding = isMaskable ? size * 0.2 : 0;
  const iconSize = size - (padding * 2);
  
  let content = '';
  
  if (isShortcut) {
    const symbol = shortcutType === 'transaction' ? '+' : '📊';
    content = `
      <rect x="${padding}" y="${padding}" width="${iconSize}" height="${iconSize}" rx="${size * 0.15}" fill="url(#grad)"/>
      <text x="${size / 2}" y="${size / 2 + iconSize * 0.15}" font-family="Arial, sans-serif" font-size="${iconSize * 0.5}" font-weight="bold" fill="white" text-anchor="middle">${symbol}</text>
    `;
  } else {
    content = `
      <rect x="${padding}" y="${padding}" width="${iconSize}" height="${iconSize}" rx="${size * 0.15}" fill="url(#grad)"/>
      <text x="${size / 2}" y="${size / 2 - iconSize * 0.05}" font-family="Arial, sans-serif" font-size="${iconSize * 0.35}" font-weight="bold" fill="white" text-anchor="middle">OF</text>
      <text x="${size / 2}" y="${size / 2 + iconSize * 0.25}" font-family="Arial, sans-serif" font-size="${iconSize * 0.15}" fill="white" text-anchor="middle">OnliFin</text>
      <circle cx="${size / 2}" cy="${size / 2 - iconSize * 0.15}" r="${iconSize * 0.15}" stroke="rgba(255,255,255,0.2)" stroke-width="${size * 0.02}" fill="none"/>
    `;
  }
  
  return `<?xml version="1.0" encoding="UTF-8"?>
<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#2563eb;stop-opacity:1" />
    </linearGradient>
  </defs>
  ${isMaskable ? `<rect width="${size}" height="${size}" fill="#3b82f6"/>` : ''}
  ${content}
</svg>`;
}

function saveSVGAsIcon(svgContent, filename) {
  const svgFilename = filename.replace('.png', '.svg');
  const svgPath = path.join(iconsDir, svgFilename);
  fs.writeFileSync(svgPath, svgContent);
  return svgFilename;
}

console.log('🎨 Gerando ícones PWA para OnliFin...\n');

console.log('📱 Gerando ícones principais:');
sizes.forEach(size => {
  const svg = generateIconSVG(size);
  const filename = saveSVGAsIcon(svg, `icon-${size}x${size}.png`);
  console.log(`  ✅ ${filename}`);
});

console.log('\n🎭 Gerando ícones maskable (Android):');
maskableSizes.forEach(size => {
  const svg = generateIconSVG(size, true);
  const filename = saveSVGAsIcon(svg, `icon-maskable-${size}x${size}.png`);
  console.log(`  ✅ ${filename}`);
});

console.log('\n⚡ Gerando ícones de shortcuts:');
['transaction', 'dashboard'].forEach(type => {
  const svg = generateIconSVG(shortcutSize, false, true, type);
  const filename = saveSVGAsIcon(svg, `shortcut-${type}.png`);
  console.log(`  ✅ ${filename}`);
});

const readmeContent = `# Ícones PWA - OnliFin

## ⚠️ Atenção

Os arquivos SVG gerados são placeholders temporários.

## 🎨 Para Criar Ícones Profissionais

### Opção 1: Usar o Gerador HTML
1. Abra no navegador: /icons/generate-icons.html
2. Os ícones serão gerados automaticamente
3. Clique com botão direito em cada ícone
4. Selecione "Salvar imagem como..."
5. Salve com os nomes corretos nesta pasta

### Opção 2: Usar Ferramenta Online
1. Crie um ícone 512x512 de alta qualidade
2. Acesse: https://realfavicongenerator.net/
3. Faça upload do seu ícone
4. Baixe todos os tamanhos gerados
5. Substitua os arquivos nesta pasta

## 📋 Ícones Necessários

- icon-72x72.png (ou .svg)
- icon-96x96.png (ou .svg)
- icon-128x128.png (ou .svg)
- icon-144x144.png (ou .svg)
- icon-152x152.png (ou .svg)
- icon-192x192.png (ou .svg)
- icon-384x384.png (ou .svg)
- icon-512x512.png (ou .svg)
- icon-maskable-192x192.png (ou .svg)
- icon-maskable-512x512.png (ou .svg)
- shortcut-transaction.png (ou .svg)
- shortcut-dashboard.png (ou .svg)
`;

fs.writeFileSync(path.join(iconsDir, 'README.md'), readmeContent);
console.log('\n📄 README.md criado em /public/icons/');

console.log('\n✅ Processo concluído!');
console.log('\n📚 Consulte PWA_DEPLOY_CHECKLIST.md para próximos passos.');
