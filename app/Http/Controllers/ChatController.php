<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenRouterService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:api')->only('sendMessage'); // Limita requisições, ajuste o limite no kernel se necessário
    }

    public function index()
    {
        return view('chat.index');
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $user = Auth::user();
        $modelConfig = $user->modelApiKeys()->where('provider', 'openrouter')->first();
        if (!$modelConfig) {
            return response()->json(['error' => 'Nenhuma configuração para OpenRouter encontrada. Configure em settings/model-keys.'], 400);
        }

        $apiKey = Crypt::decryptString($modelConfig->api_token);
        $model = $modelConfig->model;

        $openRouterService = new OpenRouterService();
        $response = $openRouterService->getChatCompletion([
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $validated['message']],
            ],
        ]);

        return response()->json($response);
    }
}
