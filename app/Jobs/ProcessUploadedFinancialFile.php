<?php

namespace App\Jobs;

use Google\Cloud\Storage\StorageClient;
use Google\Cloud\DocumentAI\V1\DocumentProcessorServiceClient;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Language\V1\LanguageServiceClient;
use Google\Cloud\Language\V1\Document AS LanguageDocument;
use Google\Cloud\Language\V1\Document\Type AS LanguageDocumentType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\FinancialFileImport;
use Illuminate\Support\Str;

class ProcessUploadedFinancialFile implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $gcsPath, public int $userId, public int $accountId) {}

    // Função auxiliar para categorizar descrição usando Natural Language API
    private function getCategoryFromNLP(string $text_content): ?string
    {
        try {
            $language = app(LanguageServiceClient::class);
            $document = (new LanguageDocument())
                ->setContent($text_content)
                ->setType(LanguageDocumentType::PLAIN_TEXT);
            
            $response = $language->classifyText($document);
            $categories = $response->getCategories();

            if (!empty($categories)) {
                // Pega a categoria com maior confiança
                $bestCategory = $categories[0]->getName();
                Log::info('Categoria NLP:', ['text' => $text_content, 'category' => $bestCategory]);
                // Pode ser necessário mapear categorias do Google para as suas categorias internas
                // Ex: /Food & Drink/Restaurants -> Restaurante
                $parts = explode('/', $bestCategory);
                return end($parts) ?: null;
            }
            $language->close();
        } catch (\Exception $e) {
            Log::error('Erro Natural Language API (categorizeText)', [
                'error' => $e->getMessage(), 
                'text' => $text_content
            ]);
        }
        return null;
    }

    public function handle(
        StorageClient $storage,
        DocumentProcessorServiceClient $docAi
    ) {
        $bucketName = config('filesystems.disks.gcs.bucket');
        $bucket = $storage->bucket($bucketName);
        $object = $bucket->object($this->gcsPath);
        $content = $object->downloadAsStream()->getContents();

        // Determine processing based on file extension
        $ext = pathinfo($this->gcsPath, PATHINFO_EXTENSION);

        $transactionData = null; // Para armazenar dados extraídos antes de criar transação

        if (in_array($ext, ['pdf'])) {
            // Document AI
            $region = env('GOOGLE_DOCUMENT_AI_REGION', 'us');
            $processorName = sprintf(
                'projects/%s/locations/%s/processors/%s',
                env('GOOGLE_CLOUD_PROJECT'),
                $region,
                '44a9676ffd9416a3'
            );
            $request = new \Google\Cloud\DocumentAI\V1\ProcessRequest([
                'name' => $processorName,
                'rawDocument' => [
                    'content' => $content,
                    'mimeType' => 'application/pdf'
                ],
            ]);
            try {
                $response = $docAi->processDocument($request);
                // Parse and save transactions from Document AI response
                $doc = $response->getDocument();
                // Convert Document proto to array
                $docArray = json_decode($doc->serializeToJsonString(), true);
                // Attempt to extract formFields from first page
                $fields = $docArray['pages'][0]['formFields'] ?? [];
                foreach ($fields as $index => $field) {
                    $name = $field['fieldName']['textAnchor']['content'] ?? '';
                    $value = $field['fieldValue']['textAnchor']['content'] ?? '';
                    // Simple heuristic: name contains 'date', 'amount', 'description'
                    if (stripos($name, 'date') !== false) {
                        $date = \Carbon\Carbon::parse($value)->format('Y-m-d');
                    } elseif (stripos($name, 'amount') !== false) {
                        $amount = (float) preg_replace('/[^\d\.,-]/', '', $value);
                    } elseif (stripos($name, 'description') !== false) {
                        $description = trim($value);
                    }
                    // When we collected all three, save
                    if (isset($date, $amount, $description)) {
                        $transactionData = [
                            'date' => $date,
                            'description' => $description,
                            'amount' => (int) round(abs($amount) * 100),
                            'type' => $amount >= 0 ? 'income' : 'expense',
                        ];
                        unset($date, $amount, $description);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Erro Document AI: ' . $e->getMessage());
            }
        } elseif (in_array($ext, ['jpg','png','jpeg'])) {
            // Processar com Vision API
            try {
                $imageAnnotator = app(ImageAnnotatorClient::class);
                $response = $imageAnnotator->textDetection($content);
                $texts = $response->getTextAnnotations();

                if (!empty($texts)) {
                    $fullText = $texts[0]->getDescription();
                    Log::info('Texto extraído pela Vision API:', ['text' => $fullText]);

                    // TODO: Implementar parsing robusto do $fullText para $date, $description, $amount
                    $parsedDate = now()->format('Y-m-d'); // Placeholder
                    $parsedDescription = 'Cupom Fiscal: ' . Str::limit($fullText, 100); // Placeholder
                    $parsedAmount = 0.01; // Placeholder, valor em reais

                    $transactionData = [
                        'date' => $parsedDate,
                        'description' => $parsedDescription,
                        'amount' => (int) round(abs($parsedAmount) * 100),
                        'type' => $parsedAmount >= 0 ? 'income' : 'expense',
                    ];
                } else {
                    Log::warning('Vision API não detectou texto na imagem.', ['gcsPath' => $this->gcsPath]);
                }
                $imageAnnotator->close();
            } catch (\Exception $e) {
                Log::error('Erro Vision API: ' . $e->getMessage(), ['gcsPath' => $this->gcsPath]);
            }
        } elseif (in_array($ext, ['csv','xlsx'])) {
            // Download locally and process with Excel import
            $local = storage_path('app/temp_' . basename($this->gcsPath));
            file_put_contents($local, $content);
            try {
                Excel::import(new FinancialFileImport($this->userId), $local);
            } catch (\Exception $e) {
                Log::error('Erro Excel import: ' . $e->getMessage());
            } finally {
                @unlink($local);
            }
            Log::info('Processamento Excel concluído, NLP não aplicado diretamente neste fluxo.');
        } else {
            Log::warning('ProcessUploadedFinancialFile: extensão não suportada: ' . $ext);
        }

        // Se extraímos dados de PDF ou Imagem, agora criamos a transação com categoria NLP
        if ($transactionData) {
            $suggestedCategoryName = $this->getCategoryFromNLP($transactionData['description']);
            $categoryId = null;
            if ($suggestedCategoryName) {
                $category = \App\Models\Category::firstOrCreate(
                    [
                        'name' => ucfirst($suggestedCategoryName),
                        'user_id' => $this->userId
                    ],
                    [
                        'type' => $transactionData['type'] == 'income' ? 'income' : 'expense']
                );
                $categoryId = $category->id;
            } else {
                // Categoria padrão se não houver sugestão
                $defaultCategory = \App\Models\Category::firstOrCreate(
                    [
                        'name' => 'Outros',
                        'user_id' => $this->userId
                    ],
                    [
                        'type' => $transactionData['type'] == 'income' ? 'income' : 'expense']
                );
                $categoryId = $defaultCategory->id;
            }

            \App\Models\Transaction::create([
                'user_id' => $this->userId,
                'account_id' => $this->accountId,
                'date' => $transactionData['date'],
                'description' => $transactionData['description'],
                'amount' => $transactionData['amount'],
                'type' => $transactionData['type'],
                'status' => 'paid',
                'category_id' => $categoryId, // Nunca nulo
            ]);
            Log::info('Transação criada com categoria NLP (se aplicável).', $transactionData);
        }

        // Optionally delete object after processing
        // $object->delete();
    }
} 