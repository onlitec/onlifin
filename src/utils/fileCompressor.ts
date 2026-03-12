/**
 * Utilitários para compressão de arquivos
 * Reduz o tamanho de arquivos CSV/OFX/QIF/XLSX antes do upload
 */

/**
 * Comprime um arquivo usando gzip
 */
export async function compressFile(file: File): Promise<Blob> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = async () => {
      try {
        const content = reader.result as ArrayBuffer;
        
        // Usar CompressionStream API (navegadores modernos)
        if (typeof CompressionStream !== 'undefined') {
          const stream = new CompressionStream('gzip');
          const writer = stream.writable.getWriter();
          const reader2 = stream.readable.getReader();
          
          const chunks: Uint8Array[] = [];
          while (true) {
            const { done, value } = await reader2.read();
            if (done) break;
            if (value) chunks.push(value);
          }
          
          await writer.close();
          const compressed = new Blob(chunks, { type: 'application/gzip' });
          resolve(compressed);
        } else {
          // Fallback: não comprimir se CompressionStream não estiver disponível
          console.warn('CompressionStream não disponível, usando arquivo original');
          resolve(new Blob([content], { type: file.type }));
        }
      } catch (error) {
        reject(error);
      }
    };
    reader.onerror = () => reject(reader.error);
    reader.readAsArrayBuffer(file);
  });
}

/**
 * Calcula o tamanho estimado após compressão
 * Retorna uma estimativa baseada no tipo de arquivo
 */
export function estimateCompressedSize(file: File): number {
  const fileName = file.name.toLowerCase();
  
  // Arquivos de texto (CSV, OFX, QIF) comprimem bem (~70-80%)
  if (fileName.endsWith('.csv') || fileName.endsWith('.ofx') || fileName.endsWith('.qif')) {
    return file.size * 0.25; // Estimativa conservadora
  }
  
  // Arquivos binários (XLSX) comprimem menos (~30-50%)
  if (fileName.endsWith('.xlsx')) {
    return file.size * 0.6;
  }
  
  return file.size;
}

/**
 * Verifica se um arquivo deve ser comprimido
 * Arquivos maiores que 1MB devem ser comprimidos
 */
export function shouldCompress(file: File): boolean {
  const ONE_MB = 1024 * 1024;
  return file.size > ONE_MB;
}

/**
 * Formata tamanho de arquivo em formato legível
 */
export function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes';

  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Calcula a taxa de compressão
 */
export function calculateCompressionRatio(originalSize: number, compressedSize: number): number {
  if (originalSize === 0) return 0;
  return ((originalSize - compressedSize) / originalSize) * 100;
}
