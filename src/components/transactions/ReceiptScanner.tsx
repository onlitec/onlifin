import * as React from 'react';
import { Camera, Upload, X, Loader2, CheckCircle2, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { processReceiptImage, type ReceiptData } from '@/services/ocrService';

interface ReceiptScannerProps {
  onDataExtracted: (data: ReceiptData) => void;
  onClose: () => void;
}

export default function ReceiptScanner({ onDataExtracted, onClose }: ReceiptScannerProps) {
  const [selectedImage, setSelectedImage] = React.useState<File | null>(null);
  const [previewUrl, setPreviewUrl] = React.useState<string | null>(null);
  const [isProcessing, setIsProcessing] = React.useState(false);
  const [progress, setProgress] = React.useState(0);
  const [error, setError] = React.useState<string | null>(null);
  const [success, setSuccess] = React.useState(false);
  const fileInputRef = React.useRef<HTMLInputElement>(null);

  const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    // Validar tipo de arquivo
    if (!file.type.startsWith('image/')) {
      setError('Por favor, selecione uma imagem válida');
      return;
    }

    // Validar tamanho (máximo 10MB antes da compressão)
    if (file.size > 10 * 1024 * 1024) {
      setError('Imagem muito grande. Máximo 10MB');
      return;
    }

    setSelectedImage(file);
    setError(null);
    setSuccess(false);

    // Criar preview
    const reader = new FileReader();
    reader.onload = (e) => {
      setPreviewUrl(e.target?.result as string);
    };
    reader.readAsDataURL(file);
  };

  const handleProcess = async () => {
    if (!selectedImage) return;

    setIsProcessing(true);
    setError(null);
    setProgress(0);

    try {
      // Simular progresso
      setProgress(10);
      await new Promise((resolve) => setTimeout(resolve, 300));

      setProgress(30);
      const receiptData = await processReceiptImage(selectedImage);

      setProgress(90);
      await new Promise((resolve) => setTimeout(resolve, 200));

      setProgress(100);
      setSuccess(true);

      // Aguardar um pouco para mostrar sucesso
      await new Promise((resolve) => setTimeout(resolve, 500));

      onDataExtracted(receiptData);
    } catch (err: any) {
      console.error('Erro ao processar cupom:', err);
      setError(err.message || 'Erro ao processar cupom fiscal');
      setProgress(0);
    } finally {
      setIsProcessing(false);
    }
  };

  const handleClear = () => {
    setSelectedImage(null);
    setPreviewUrl(null);
    setError(null);
    setSuccess(false);
    setProgress(0);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  return (
    <Card className="w-full">
      <CardContent className="p-6">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <Camera className="w-5 h-5 text-primary" />
            <h3 className="text-lg font-semibold">Escanear Cupom Fiscal</h3>
          </div>
          <Button variant="ghost" size="icon" onClick={onClose}>
            <X className="w-4 h-4" />
          </Button>
        </div>

        <div className="space-y-4">
          {/* Área de upload */}
          {!previewUrl && (
            <div
              className="border-2 border-dashed border-border rounded-lg p-8 text-center cursor-pointer hover:border-primary transition-colors"
              onClick={() => fileInputRef.current?.click()}
            >
              <Upload className="w-12 h-12 mx-auto mb-4 text-muted-foreground" />
              <p className="text-sm font-medium mb-1">
                Clique para selecionar uma imagem
              </p>
              <p className="text-xs text-muted-foreground">
                Tire uma foto do cupom fiscal ou QR code
              </p>
              <p className="text-xs text-muted-foreground mt-2">
                Formatos: JPG, PNG, WEBP (máx. 10MB)
              </p>
            </div>
          )}

          {/* Preview da imagem */}
          {previewUrl && (
            <div className="relative">
              <img
                src={previewUrl}
                alt="Preview do cupom"
                className="w-full h-auto max-h-96 object-contain rounded-lg border"
              />
              {!isProcessing && !success && (
                <Button
                  variant="destructive"
                  size="icon"
                  className="absolute top-2 right-2"
                  onClick={handleClear}
                >
                  <X className="w-4 h-4" />
                </Button>
              )}
            </div>
          )}

          {/* Barra de progresso */}
          {isProcessing && (
            <div className="space-y-2">
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Processando cupom...</span>
                <span className="font-medium">{progress}%</span>
              </div>
              <div className="w-full bg-secondary rounded-full h-2 overflow-hidden">
                <div
                  className="bg-primary h-full transition-all duration-300"
                  style={{ width: `${progress}%` }}
                />
              </div>
            </div>
          )}

          {/* Mensagem de sucesso */}
          {success && (
            <Alert className="border-green-500 bg-green-50 dark:bg-green-950">
              <CheckCircle2 className="w-4 h-4 text-green-600" />
              <AlertDescription className="text-green-800 dark:text-green-200">
                Cupom processado com sucesso! Preenchendo formulário...
              </AlertDescription>
            </Alert>
          )}

          {/* Mensagem de erro */}
          {error && (
            <Alert variant="destructive">
              <AlertCircle className="w-4 h-4" />
              <AlertDescription>{error}</AlertDescription>
            </Alert>
          )}

          {/* Botões de ação */}
          <div className="flex gap-2">
            {selectedImage && !isProcessing && !success && (
              <>
                <Button onClick={handleProcess} className="flex-1">
                  <Camera className="w-4 h-4 mr-2" />
                  Processar Cupom
                </Button>
                <Button variant="outline" onClick={handleClear}>
                  Limpar
                </Button>
              </>
            )}

            {isProcessing && (
              <Button disabled className="flex-1">
                <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                Processando...
              </Button>
            )}
          </div>

          {/* Dicas */}
          <div className="bg-muted p-4 rounded-lg">
            <p className="text-sm font-medium mb-2">💡 Dicas para melhor leitura:</p>
            <ul className="text-xs text-muted-foreground space-y-1">
              <li>• Tire a foto em boa iluminação</li>
              <li>• Mantenha o cupom reto e sem dobras</li>
              <li>• Certifique-se de que o texto está legível</li>
              <li>• Para QR code, centralize-o na foto</li>
            </ul>
          </div>
        </div>

        {/* Input oculto */}
        <input
          ref={fileInputRef}
          type="file"
          accept="image/*"
          capture="environment"
          className="hidden"
          onChange={handleFileSelect}
        />
      </CardContent>
    </Card>
  );
}
