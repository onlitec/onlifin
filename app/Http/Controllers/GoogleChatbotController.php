<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Google\Cloud\Storage\StorageClient;
use App\Jobs\ProcessUploadedFinancialFile;
use App\Services\AIService;
use App\Services\AIConfigService;
use Exception;

class GoogleChatbotController extends Controller
{
    /**
     * Handle chat messages and file uploads using AI Service.
     */
    public function ask(Request $request)
    {
        try {
            Log::info('GoogleChatbotController@ask iniciado', ['user_id' => auth()->id()]);

            $sessionId = session()->getId();

            // Validação básica da requisição
            $validated = $request->validate([
                'message' => 'nullable|string|max:5000',
                'file' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,csv,xls,xlsx,ofx,qif,txt', // 10MB max
            ]);

            $messageText = $validated['message'] ?? '';

            // Carregar configuração de IA dinâmica
            $aiConfigService = new AIConfigService();
            $config = $aiConfigService->getAIConfig();
            
            if (!$config || !$config['is_configured']) {
                throw new Exception('Nenhuma configuração de IA encontrada. Configure um provedor de IA primeiro.');
            }
            
            // Initialize AI Service with dynamic configuration
            $aiService = new AIService(
                $config['provider'],
                $config['model'],
                $config['api_key'],
                $config['endpoint'] ?? null,
                $config['system_prompt'] ?? null,
                $config['chat_prompt'] ?? null,
                $config['import_prompt'] ?? null
            );

            // Se um arquivo foi enviado, processa-o primeiro
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $file = $request->file('file');
                $originalFileName = $file->getClientOriginalName();
                $fileExtension = $file->getClientOriginalExtension();
                $gcsPath = 'uploads/' . uniqid() . '-' . $originalFileName;

                try {
                    // Resolve GCS client only if file upload is needed
                    $storage = app(StorageClient::class);
                    $bucketName = config('filesystems.disks.gcs.bucket');
                    if (!$bucketName) {
                        Log::error('Bucket GCS não configurado.');
                        return response()->json(['error' => 'Erro de configuração do servidor (Bucket).'], 500);
                    }
                    $bucket = $storage->bucket($bucketName);
                    $object = $bucket->upload(
                        fopen($file->getPathname(), 'r'),
                        ['name' => $gcsPath]
                    );
                    Log::info('Arquivo enviado ao GCS', ['path' => $object->name()]);

                    // Disparar o job de processamento
                    $accountId = $request->input('account_id'); 
                    ProcessUploadedFinancialFile::dispatch($object->name(), auth()->id(), $accountId);

                    $fileReplyMessage = 'Arquivo "' . $originalFileName . '" recebido e enviado para processamento.';
                    if (empty($messageText)) {
                        return response()->json(['reply' => $fileReplyMessage], 200);
                    }
                    // Se houver texto junto com o arquivo, anexa a mensagem de upload e continua para análise
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

            // Analisar mensagem usando AI Service
            $response = $aiService->analyze($messageText);

            return response()->json(['reply' => $response]);
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