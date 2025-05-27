<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Storage\StorageClient;
use App\Jobs\ProcessUploadedFinancialFile;

class GoogleChatbotController extends Controller
{
    /**
     * Handle chat messages and file uploads via Dialogflow.
     */
    public function ask(Request $request)
    {
        try {
            // Resolve GCP clients inside try to catch instantiation errors
            $sessions = app(SessionsClient::class);
            $storage = app(StorageClient::class);
            Log::info('GoogleChatbotController@ask iniciado', ['user_id' => auth()->id()]);

            $sessionId = session()->getId();
            // Obter o projectId do arquivo de configuração, que já lê de .env ou gcp.php
            $projectId = config('services.gcp.project_id');
            if (!$projectId) {
                Log::error('Google Cloud Project ID não configurado.');
                return response()->json(['error' => 'Erro de configuração do servidor (Project ID).'], 500);
            }
            $sessionName = SessionsClient::sessionName($projectId, $sessionId);

            // Validação básica da requisição
            $validated = $request->validate([
                'message' => 'nullable|string|max:5000',
                'file' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,csv,xls,xlsx,ofx,qif,txt', // 10MB max
            ]);

            $messageText = $validated['message'] ?? '';

            // Se um arquivo foi enviado, processa-o primeiro
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $file = $request->file('file');
                $originalFileName = $file->getClientOriginalName();
                $fileExtension = $file->getClientOriginalExtension();
                $gcsPath = 'uploads/' . uniqid() . '-' . $originalFileName;

                try {
                    $bucketName = config('filesystems.disks.gcs.bucket');
                    if (!$bucketName) {
                        Log::error('Nome do bucket GCS não configurado.');
                        return response()->json(['error' => 'Erro de configuração do servidor (Bucket).'], 500);
                    }
                    $bucket = $storage->bucket($bucketName);
                    
                    $object = $bucket->upload(
                        fopen($file->getRealPath(), 'r'),
                        ['name' => $gcsPath]
                    );
                    Log::info('Arquivo enviado ao GCS', ['path' => $object->name()]);

                    // Disparar o job de processamento
                    // TODO: Considerar se account_id é realmente necessário aqui ou se o job pode inferir/obter de outra forma.
                    // Por enquanto, vamos remover se não estiver vindo do request para evitar erros.
                    $accountId = $request->input('account_id'); 
                    ProcessUploadedFinancialFile::dispatch($object->name(), auth()->id(), $accountId);

                    $fileReplyMessage = 'Arquivo "' . $originalFileName . '" recebido e enviado para processamento.';
                    if (empty($messageText)) {
                        return response()->json(['reply' => $fileReplyMessage], 200);
                    }
                    // Se houver texto junto com o arquivo, anexa a mensagem de upload e continua para Dialogflow
                    $messageText = $fileReplyMessage . "\n\n" . $messageText;

                } catch (\Exception $e) {
                    Log::error('Erro ao fazer upload do arquivo para GCS ou despachar job', ['error' => $e->getMessage()]);
                    return response()->json(['error' => 'Falha ao processar o arquivo.'], 500);
                }
            } elseif ($request->hasFile('file') && !$request->file('file')->isValid()) {
                return response()->json(['error' => 'Arquivo inválido ou corrompido.'], 400);
            }

            // Se não houver mensagem de texto (e nenhum arquivo válido foi processado para gerar uma mensagem de texto)
            if (empty($messageText)) {
                return response()->json(['reply' => 'Por favor, envie uma mensagem ou um arquivo válido.'], 200);
            }

            // Detectar intenção no Dialogflow para mensagens de texto
            $queryInput = new \Google\Cloud\Dialogflow\V2\QueryInput([
                'text' => [
                    'text' => $messageText,
                    'language_code' => 'pt-BR'
                ]
            ]);

            $response = $sessions->detectIntent($sessionName, $queryInput);
            $fulfillment = $response->getQueryResult()->getFulfillmentText();

            return response()->json(['reply' => $fulfillment]);
        } catch (\Throwable $e) {
            // Log any error or Throwable
            Log::error('Erro geral GoogleChatbotController@ask', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'exception_class' => get_class($e)]);
            // Return JSON with the real error message and trace for debugging
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => explode("\n", $e->getTraceAsString())
            ], method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
        }
    }

    /**
     * Exibe a interface do chatbot.
     * Atualmente, o widget do chatbot é injetado globalmente via app.blade.php.
     * Esta rota/método pode não ser estritamente necessária se não houver uma página dedicada ao chatbot.
     * Mantendo por enquanto, caso uma página específica seja desejada no futuro.
     */
    public function index(\App\Services\AIConfigService $aiConfigService)
    {
        if (view()->exists('chatbot.index')) {
            // As linhas que buscam config e accounts precisam ser descomentadas
            // e usadas aqui se a view chatbot.index for renderizada.
            $config = $aiConfigService->getAIConfig(); 
            $user = auth()->user();
            $accounts = collect(); // Inicializa como coleção vazia
            if ($user) {
                $accounts = $user->accounts()->where('active', true)->orderBy('name')->get();
            }
            return view('chatbot.index', compact('config', 'accounts'));
        } 
        // Se não houver view chatbot.index, redireciona para o dashboard como fallback
        return redirect()->route('dashboard')->with('info', 'O Chatbot está disponível no canto inferior direito.');
    }
} 