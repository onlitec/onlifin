<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MoeMizrak\LaravelOpenRouter\Facades\LaravelOpenRouter;
use MoeMizrak\LaravelOpenRouter\DTO\ChatData;
use MoeMizrak\LaravelOpenRouter\DTO\MessageData;
use Illuminate\Support\Facades\Storage; // Para lidar com uploads

class BankStatementController extends Controller
{
    public function uploadAndAnalyze(Request $request)
    {
        // Validação e upload do arquivo (exemplo)
        $request->validate([
            'file' => 'required|file|mimes:pdf,csv,ofx,qif,qfx,xls,xlsx,txt|max:2048', // Limite de 2MB, ajuste se necessário
        ]);

        $file = $request->file('file');
        $path = $file->store('bank_statements'); // Armazena o arquivo temporariamente

        // Ler o conteúdo do arquivo (simplificado; use parsing adequado para cada formato)
        $content = Storage::get($path); // Lê o conteúdo como string

        // Prompt para a IA baseado no fornecido
        $prompt = <<<PROMPT
Você é uma IA especializada em extração de dados de transações financeiras. Analise o texto bruto fornecido e retorne **apenas** um objeto JSON com as informações extraídas e formatadas. Não adicione nenhum texto fora do JSON. Siga estes passos:

1. **Extração de Dados**: Extraia do texto:
   - 'date': Data no formato 'DD/MM/AAAA'.
   - 'identificador': Qualquer ID único como UUID.
   - 'bank_data': Informações de banco, agência e conta.
   - 'name': Nome de pessoa ou empresa.
   - 'tax_id': CPF ou CNPJ.
   - 'category': Categoria com base em palavras-chave como 'Mercado', 'padaria', 'supermercado', 'loja', 'sorveteria', 'açougue', 'uber', 'armazém'. Se ausente ou ambígua, busque referências ou crie uma categoria resumida do texto.
   - 'transaction_type': 'income' ou 'expense'.
   - 'descrição': Descrição adicional baseada no contexto.
   - 'observações': Notas ou observações se disponíveis.

2. **Formatação da Saída**: Retorne um array de objetos JSON, cada um representando uma transação formatada. Se não encontrar algum campo, use 'null'. Garanta precisão e saída em JSON puro.

Exemplo de entrada e saída:
Entrada: 'Texto bruto: Recebimento via TED em 15/03/2024. ID: a1b2c3d4-e5f6-7890-1234-567890abcdef. Banco: Banco do Brasil, Agência: 1234-5, Conta: 67890-1. Nome: BEATRIZ DOMINGOS GALVAO FREIRE, CPF: 57.815.082/0001-40. Transferência recebida.'
Saída: [{"date":"15/03/2024","identificador":"a1b2c3d4-e5f6-7890-1234-567890abcdef","bank_data":"Banco do Brasil, Agência: 1234-5, Conta: 67890-1","name":"BEATRIZ DOMINGOS GALVAO FREIRE","tax_id":"57.815.082/0001-40","category":"Transferência recebida","transaction_type":"income","descrição":"Transferência recebida via TED","observações":"null"}]
PROMPT;

        // Montar a mensagem para a IA
        $messageData = new MessageData(
            role: 'user',
            content: $prompt . '\n\nTexto bruto: ' . $content, // Inclui o conteúdo do arquivo
        );

        $chatData = new ChatData(
            messages: [$messageData],
            model: 'mistralai/mistral-7b-instruct:free', // Use um modelo adequado; ajuste se necessário
            response_format: new ResponseFormatData(type: 'json_object'), // Garante saída estruturada
        );

        // Enviar para OpenRouter
        try {
            $response = LaravelOpenRouter::chatRequest($chatData);
            $jsonResponse = json_decode($response->choices[0]->message->content, true); // Decodifica a resposta JSON

            // Retornar a resposta ao usuário
            return response()->json($jsonResponse, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Falha na análise: ' . $e->getMessage()], 500);
        }
    }
}
