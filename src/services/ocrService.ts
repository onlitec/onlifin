// Serviço de OCR para leitura de cupons fiscais
const APP_ID = import.meta.env.VITE_APP_ID;
const OCR_API_URL = 'https://api-integrations.appmedo.com/app-7xkeeoe4bsap/api-Xa6JxbyEgZea/parse/image';
const OCR_API_KEY = 'K87649693488957';
const GEMINI_API_URL = 'https://api-integrations.appmedo.com/app-7xkeeoe4bsap/api-rLob8RdzAOl9/v1beta/models/gemini-2.5-flash:streamGenerateContent?alt=sse';

export interface ReceiptData {
  storeName?: string;
  storeDocument?: string; // CNPJ
  date?: string;
  time?: string;
  totalAmount?: number;
  items?: Array<{
    description: string;
    quantity?: number;
    unitPrice?: number;
    totalPrice?: number;
  }>;
  paymentMethod?: string;
  accessKey?: string; // Chave de acesso NFC-e
}

/**
 * Comprime uma imagem para WEBP mantendo qualidade e tamanho abaixo de 1MB
 */
export async function compressImage(file: File, maxSizeBytes = 1048576): Promise<Blob> {
  return new Promise((resolve, reject) => {
    const img = new Image();
    const reader = new FileReader();

    reader.onload = (e) => {
      img.src = e.target?.result as string;
    };

    img.onload = async () => {
      const canvas = document.createElement('canvas');
      let width = img.width;
      let height = img.height;

      // Redimensionar se maior que 1080p
      const maxDimension = 1920;
      if (width > maxDimension || height > maxDimension) {
        if (width > height) {
          height = (height / width) * maxDimension;
          width = maxDimension;
        } else {
          width = (width / height) * maxDimension;
          height = maxDimension;
        }
      }

      canvas.width = width;
      canvas.height = height;

      const ctx = canvas.getContext('2d');
      if (!ctx) {
        reject(new Error('Não foi possível criar contexto do canvas'));
        return;
      }

      ctx.drawImage(img, 0, 0, width, height);

      // Tentar diferentes qualidades até ficar abaixo de 1MB
      let quality = 0.9;
      let blob: Blob | null = null;

      const tryCompress = async (q: number): Promise<Blob> => {
        return new Promise((res) => {
          canvas.toBlob(
            (b) => {
              if (b) res(b);
            },
            'image/webp',
            q
          );
        });
      };

      while (quality > 0.1) {
        blob = await tryCompress(quality);
        if (blob && blob.size <= maxSizeBytes) {
          break;
        }
        quality -= 0.1;
      }

      if (blob) {
        resolve(blob);
      } else {
        reject(new Error('Não foi possível comprimir a imagem'));
      }
    };

    img.onerror = () => {
      reject(new Error('Erro ao carregar imagem'));
    };

    reader.onerror = () => {
      reject(new Error('Erro ao ler arquivo'));
    };

    reader.readAsDataURL(file);
  });
}

/**
 * Converte Blob para Base64
 */
async function blobToBase64(blob: Blob): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onloadend = () => {
      const base64 = reader.result as string;
      resolve(base64);
    };
    reader.onerror = reject;
    reader.readAsDataURL(blob);
  });
}

/**
 * Extrai texto de uma imagem usando OCR.space API
 */
export async function extractTextFromImage(imageFile: File): Promise<string> {
  try {
    // Comprimir imagem se necessário
    let imageToUpload: Blob = imageFile;
    if (imageFile.size > 1048576) {
      console.log('Comprimindo imagem...');
      imageToUpload = await compressImage(imageFile);
      console.log(`Imagem comprimida: ${imageFile.size} -> ${imageToUpload.size} bytes`);
    }

    // Converter para base64
    const base64Image = await blobToBase64(imageToUpload);

    // Chamar API OCR
    const formData = new FormData();
    formData.append('base64Image', base64Image);
    formData.append('language', 'por'); // Português
    formData.append('isOverlayRequired', 'false');
    formData.append('detectOrientation', 'true');
    formData.append('scale', 'true');
    formData.append('OCREngine', '2'); // Engine 2 para melhor detecção

    const response = await fetch(OCR_API_URL, {
      method: 'POST',
      headers: {
        'apikey': OCR_API_KEY,
      },
      body: formData,
    });

    if (!response.ok) {
      throw new Error(`Erro na API OCR: ${response.status}`);
    }

    const data = await response.json();

    if (data.IsErroredOnProcessing) {
      throw new Error(data.ErrorMessage || 'Erro ao processar imagem');
    }

    if (!data.ParsedResults || data.ParsedResults.length === 0) {
      throw new Error('Nenhum texto encontrado na imagem');
    }

    const extractedText = data.ParsedResults[0].ParsedText;
    if (!extractedText || extractedText.trim() === '') {
      throw new Error('Nenhum texto foi extraído da imagem');
    }

    return extractedText;
  } catch (error: any) {
    console.error('Erro ao extrair texto:', error);
    throw new Error(error.message || 'Erro ao processar imagem');
  }
}

/**
 * Analisa texto extraído usando IA para estruturar dados do cupom fiscal
 */
export async function parseReceiptText(extractedText: string): Promise<ReceiptData> {
  try {
    const prompt = `Analise o seguinte texto extraído de um cupom fiscal brasileiro (NFC-e ou NF-e) e extraia as informações em formato JSON.

Texto do cupom:
${extractedText}

Retorne APENAS um objeto JSON válido com a seguinte estrutura (sem markdown, sem explicações):
{
  "storeName": "nome do estabelecimento",
  "storeDocument": "CNPJ do estabelecimento",
  "date": "data no formato YYYY-MM-DD",
  "time": "hora no formato HH:MM",
  "totalAmount": valor_total_numerico,
  "items": [
    {
      "description": "descrição do item",
      "quantity": quantidade_numerica,
      "unitPrice": preco_unitario_numerico,
      "totalPrice": preco_total_numerico
    }
  ],
  "paymentMethod": "forma de pagamento",
  "accessKey": "chave de acesso da nota (44 dígitos)"
}

Se algum campo não estiver disponível, use null. Certifique-se de que os valores numéricos sejam números, não strings.`;

    const response = await fetch(GEMINI_API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-App-Id': APP_ID,
      },
      body: JSON.stringify({
        contents: [
          {
            role: 'user',
            parts: [{ text: prompt }],
          },
        ],
      }),
    });

    if (!response.ok) {
      throw new Error(`Erro na API Gemini: ${response.status}`);
    }

    const reader = response.body?.getReader();
    if (!reader) {
      throw new Error('Não foi possível ler resposta da API');
    }

    let fullText = '';
    const decoder = new TextDecoder();

    // Ler stream de resposta
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;

      const chunk = decoder.decode(value);
      const lines = chunk.split('\n');

      for (const line of lines) {
        if (line.startsWith('data: ')) {
          try {
            const jsonData = JSON.parse(line.substring(6));
            const text = jsonData.candidates?.[0]?.content?.parts?.[0]?.text;
            if (text) {
              fullText += text;
            }
          } catch (e) {
            // Ignorar erros de parsing de linhas individuais
          }
        }
      }
    }

    // Extrair JSON da resposta
    const jsonMatch = fullText.match(/\{[\s\S]*\}/);
    if (!jsonMatch) {
      throw new Error('Não foi possível extrair dados estruturados do cupom');
    }

    const receiptData: ReceiptData = JSON.parse(jsonMatch[0]);
    return receiptData;
  } catch (error: any) {
    console.error('Erro ao analisar texto:', error);
    throw new Error(error.message || 'Erro ao processar dados do cupom');
  }
}

/**
 * Processa imagem de cupom fiscal completa (OCR + parsing)
 */
export async function processReceiptImage(imageFile: File): Promise<ReceiptData> {
  // Extrair texto
  const extractedText = await extractTextFromImage(imageFile);

  // Analisar texto
  const receiptData = await parseReceiptText(extractedText);

  return receiptData;
}
