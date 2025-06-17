<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Google\Cloud\Storage\StorageClient;
// Dialogflow SessionsClient removido - usando AIService para chat
use Google\Cloud\DocumentAI\V1\DocumentProcessorServiceClient;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Language\V1\LanguageServiceClient;
use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\Core\ServiceBuilder;
use Illuminate\Support\Facades\Log;

class GcpServiceProvider extends ServiceProvider
{
    /**
     * Register GCP clients in the container.
     */
    public function register()
    {
        // GCP services disabled - credentials not required
        $hasCredentials = false;
        $credentialsArray = null;

        // Cloud Storage - disabled (credentials not available)

        // Dialogflow Sessions client removido - usando AIService para chat

        // Document AI - only if credentials are available
        if ($hasCredentials && $credentialsArray) {
            $this->app->singleton(DocumentProcessorServiceClient::class, function () use ($credentialsArray) {
                $region = config('services.gcp.document_ai_region', 'us');
                $endpoint = sprintf('%s-documentai.googleapis.com', $region);
                return new DocumentProcessorServiceClient([
                    'credentials' => $credentialsArray,
                    'apiEndpoint' => $endpoint,
                ]);
            });
        }

        // Vision API - only if credentials are available
        if ($hasCredentials && $credentialsArray) {
            $this->app->singleton(ImageAnnotatorClient::class, function () use ($credentialsArray) {
                return new ImageAnnotatorClient([
                    'credentials' => $credentialsArray,
                ]);
            });
        }

        // Natural Language API - only if credentials are available
        if ($hasCredentials && $credentialsArray) {
            $this->app->singleton(LanguageServiceClient::class, function () use ($credentialsArray) {
                return new LanguageServiceClient([
                    'credentials' => $credentialsArray,
                ]);
            });
        }

        // Secret Manager - only if credentials are available
        if ($hasCredentials && $credentialsArray) {
            $this->app->singleton(SecretManagerServiceClient::class, function () use ($credentialsArray) {
                return new SecretManagerServiceClient([
                    'credentials' => $credentialsArray,
                ]);
            });
        }

        // BigQuery - disabled (credentials not available)
    }

    public function boot()
    {
        //
    }
}