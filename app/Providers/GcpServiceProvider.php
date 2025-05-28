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
        // Resolve credentials file path once and decode JSON credentials
        $credEnv = config('services.gcp.credentials');
        $credPath = $credEnv ? base_path($credEnv) : null;
        
        // Check if credentials file exists, but don't throw exception immediately
        $hasCredentials = $credPath && file_exists($credPath) && is_readable($credPath);
        $credentialsArray = null;
        
        if ($hasCredentials) {
            $credentialsArray = json_decode(file_get_contents($credPath), true);
            Log::info('GCP credentials loaded successfully', ['path' => $credPath]);
        } else {
            Log::warning('GCP credentials file not found or unreadable. Some GCP services may not be available.', ['path' => $credPath]);
        }

        // Cloud Storage - only if credentials are available
        if ($hasCredentials) {
            $this->app->singleton(StorageClient::class, function () use ($credPath) {
                return new StorageClient([
                    'projectId' => config('services.gcp.project_id'),
                    'keyFilePath' => $credPath,
                ]);
            });
        }

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

        // BigQuery - only if credentials are available
        if ($hasCredentials) {
            $this->app->singleton(BigQueryClient::class, function ($app) {
                $projectId = config('services.gcp.project_id');
                $keyFilePath = config('services.gcp.credentials');
                $keyFileFullPath = $keyFilePath ? base_path($keyFilePath) : null;

                Log::info('GcpServiceProvider: Attempting to instantiate BigQueryClient using config.', [
                    'config_project_id' => $projectId,
                    'config_credentials_path' => $keyFilePath,
                    'resolved_key_file_path' => $keyFileFullPath,
                    'keyFileExists' => $keyFileFullPath ? file_exists($keyFileFullPath) : false,
                    'keyFileIsReadable' => $keyFileFullPath ? is_readable($keyFileFullPath) : false,
                ]);

                if (!$projectId) {
                    $errorMessage = 'GOOGLE_CLOUD_PROJECT (via config services.gcp.project_id) is not set or not loaded correctly.';
                    Log::error('GcpServiceProvider: ' . $errorMessage);
                    throw new \Exception($errorMessage);
                }

                if (!$keyFilePath) {
                    $errorMessage = 'GOOGLE_APPLICATION_CREDENTIALS (via config services.gcp.credentials) is not set or not loaded correctly.';
                    Log::error('GcpServiceProvider: ' . $errorMessage);
                    throw new \Exception($errorMessage);
                }

                if (!$keyFileFullPath || !file_exists($keyFileFullPath)) {
                    $errorMessage = 'Credentials file (via config services.gcp.credentials) does not exist at path: ' . $keyFileFullPath;
                    Log::error('GcpServiceProvider: ' . $errorMessage);
                    throw new \Exception($errorMessage);
                }

                try {
                    return new BigQueryClient([
                        'projectId' => $projectId,
                        'keyFilePath' => $keyFileFullPath,
                        'suppressKeyFileNotice' => true,
                    ]);
                } catch (\Exception $e) {
                    Log::error('GcpServiceProvider: Error instantiating BigQueryClient: ' . $e->getMessage(), [
                        'exception_class' => get_class($e),
                        'exception_message' => $e->getMessage(),
                        'exception_trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            });
        }
    }

    public function boot()
    {
        //
    }
}