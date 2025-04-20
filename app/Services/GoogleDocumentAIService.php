<?php

namespace App\Services;

use Google\Cloud\DocumentAI\DocumentProcessorServiceClient;
use Illuminate\Support\Facades\Config;

class GoogleDocumentAIService
{
    private $client;

    public function __construct()
    {
        $config = [
            'credentials' => Config::get('google_cloud.credentials_path'),
            'apiEndpoint' => Config::get('google_cloud.api_endpoint'),
            'timeout' => Config::get('google_cloud.timeout')
        ];

        $this->client = new DocumentProcessorServiceClient($config);
    }

    /**
     * Processa um documento usando o Document AI
     * @param string $filePath Caminho do arquivo a ser processado
     * @return array Dados extraídos do documento
     * @throws \Exception
     */
    public function processDocument(string $filePath, string $processorType = 'extratos'): array
    {
        try {
            $project_id = Config::get('google_cloud.project_id');
            $location = Config::get('google_cloud.location');
            $processorConfig = Config::get('document_ai_processors.processors.' . $processorType);
            $processor_id = $processorConfig['id'];

            $name = "projects/{$project_id}/locations/{$location}/processors/{$processor_id}";

            // Verifica se o tipo de arquivo é suportado
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $processorConfig['supported_types'])) {
                throw new \Exception("Tipo de arquivo não suportado pelo processador {$processorType}");
            }

            $content = file_get_contents($filePath);
            $document = new \Google\Cloud\DocumentAI\V1\Document();
            $document->setContent($content);
            $document->setMimeType('application/' . $fileExtension);

            try {
                $response = $this->client->processDocument($name, $document);
                $document = $response->getDocument();
            } catch (\Exception $e) {
                throw new \Exception("Erro ao processar documento: {$e->getMessage()}");
            }

            return $this->extractDataFromDocument($document);
        } catch (\Exception $e) {
            throw new \Exception("Erro ao processar documento: {$e->getMessage()}");
        }
    }

    /**
     * Extrai dados relevantes do documento processado
     * @param \Google\Cloud\DocumentAI\V1\Document $document
     * @return array
     */
    private function extractDataFromDocument(\Google\Cloud\DocumentAI\V1\Document $document): array
    {
        $data = [
            'transactions' => [],
            'bank_info' => []
        ];
        
        foreach ($document->getEntities() as $entity) {
            $type = $entity->getType();
            $content = $entity->getContent();

            switch ($type) {
                case 'transaction.date':
                    $data['transactions'][] = [
                        'date' => $content,
                        'amount' => null,
                        'description' => null
                    ];
                    break;
                case 'transaction.amount':
                    if (!empty($data['transactions'])) {
                        $lastTransaction = end($data['transactions']);
                        $key = key($data['transactions']);
                        $data['transactions'][$key]['amount'] = $content;
                    }
                    break;
                case 'transaction.description':
                    if (!empty($data['transactions'])) {
                        $lastTransaction = end($data['transactions']);
                        $key = key($data['transactions']);
                        $data['transactions'][$key]['description'] = $content;
                    }
                    break;
                case 'bank.name':
                    $data['bank_info']['name'] = $content;
                    break;
                case 'bank.account':
                    $data['bank_info']['account'] = $content;
                    break;
                case 'bank.balance':
                    $data['bank_info']['balance'] = $content;
                    break;
                default:
                    $data[$type] = $content;
            }
        }

        return $data;
    }
}
