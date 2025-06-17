<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use Illuminate\Support\Facades\Storage;
use Google\Cloud\BigQuery\BigQueryClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExportTransactionsToBigQuery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:export-transactions-bq {--dataset=financial_data_prod} {--table=transactions_exported}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports transactions to a CSV file and optionally uploads to BigQuery.';

    /**
     * Execute the console command.
     */
    public function handle(BigQueryClient $bigQuery)
    {
        $this->info('Debug: Starting ExportTransactionsToBigQuery command.');
        $this->info('Debug: GOOGLE_CLOUD_PROJECT from env(): ' . (env('GOOGLE_CLOUD_PROJECT') ?: 'NULL'));
        $this->info('Debug: GOOGLE_APPLICATION_CREDENTIALS from env(): ' . (env('GOOGLE_APPLICATION_CREDENTIALS') ?: 'NULL'));
        $this->info('Debug: services.gcp.project_id from config(): ' . (config('services.gcp.project_id') ?: 'NULL'));
        $this->info('Debug: services.gcp.credentials from config(): ' . (config('services.gcp.credentials') ?: 'NULL'));

        // Verificação explícita para sair mais cedo se a configuração crucial estiver faltando
        if (!config('services.gcp.project_id')) {
            $this->error('Error: services.gcp.project_id is not set in config. Check config/services.php and your .env file.');
            Log::error('ExportTransactionsToBigQuery: services.gcp.project_id is null in config.');
            return 1; // Código de erro
        }

        if (!config('services.gcp.credentials')) {
            $this->error('Error: services.gcp.credentials is not set in config. Check config/services.php and your .env file.');
            Log::error('ExportTransactionsToBigQuery: services.gcp.credentials is null in config.');
            return 1; // Código de erro
        }

        $this->info('Iniciando exportação de transações para BigQuery...');

        $datasetId = $this->option('dataset');
        $tableId = $this->option('table');
        $filename = 'bq_transactions_export_' . Carbon::now()->format('YmdHis') . '.csv';
        $filePath = 'bq_exports/' . $filename; // Salvar em storage/app/bq_exports/

        $this->line("Exportando transações para: {$filePath}");

        // Cabeçalho do CSV
        $csvHeader = [
            'transaction_id',
            'user_id',
            'account_id',
            'category_id',
            'company_id',
            'transaction_date', // Formato YYYY-MM-DD HH:MM:SS para TIMESTAMP no BQ
            'description',
            'amount',       // Em REAIS (float), não em centavos
            'type',         // income or expense
            'status',       // paid or pending
            'created_at_bq', // Data de criação do registro no BQ
        ];

        Storage::disk('local')->put($filePath, implode(',', $csvHeader));

        $count = 0;
        Transaction::with('category')->chunk(500, function ($transactions) use ($filePath, &$count) {
            $csvData = '';
            foreach ($transactions as $transaction) {
                $data = [
                    $transaction->id,
                    $transaction->user_id,
                    $transaction->account_id,
                    $transaction->category_id,
                    $transaction->company_id,
                    Carbon::parse($transaction->date)->format('Y-m-d H:i:s'),
                    "\"{$transaction->description}\"", // Aspas para strings com vírgula
                    $transaction->amount / 100, // Converter centavos para reais
                    $transaction->type,
                    $transaction->status,
                    Carbon::now()->format('Y-m-d H:i:s'),
                ];
                $csvData .= "\n" . implode(',', $data);
                $count++;
            }
            Storage::disk('local')->append($filePath, $csvData);
            $this->line("Processado {$count} transações...");
        });

        $this->info("Exportação para CSV concluída: {$count} transações exportadas para storage/app/{$filePath}");

        // Etapa de Upload para BigQuery (requer tabela pré-existente com schema compatível)
        $this->line("Tentando carregar CSV para BigQuery: {$datasetId}.{$tableId}");

        try {
            $dataset = $bigQuery->dataset($datasetId);
            if (!$dataset->exists()) {
                $this->warn("Dataset {$datasetId} não existe no BigQuery. Por favor, crie-o primeiro.");
                return 1;
            }
            $table = $dataset->table($tableId);
            
            // Definir o schema da tabela (deve corresponder ao CSV)
            // Nota: Para TIMESTAMP, o BigQuery espera YYYY-MM-DD HH:MM:SS ou similar.
            // Para DATE, YYYY-MM-DD.
            $schema = [
                'fields' => [
                    ['name' => 'transaction_id', 'type' => 'INTEGER'],
                    ['name' => 'user_id', 'type' => 'INTEGER'],
                    ['name' => 'account_id', 'type' => 'INTEGER'],
                    ['name' => 'category_id', 'type' => 'INTEGER'],
                    ['name' => 'company_id', 'type' => 'INTEGER'],
                    ['name' => 'transaction_date', 'type' => 'TIMESTAMP'],
                    ['name' => 'description', 'type' => 'STRING'],
                    ['name' => 'amount', 'type' => 'FLOAT'], // Ou NUMERIC para precisão monetária
                    ['name' => 'type', 'type' => 'STRING'],
                    ['name' => 'status', 'type' => 'STRING'],
                    ['name' => 'created_at_bq', 'type' => 'TIMESTAMP'],
                ]
            ];

            // Se a tabela não existir, tenta criar com o schema
            if (!$table->exists()) {
                $this->line("Tabela {$tableId} não existe. Tentando criar com schema inferido...");
                try {
                    $dataset->createTable($tableId, ['schema' => $schema]);
                    $this->info("Tabela {$tableId} criada com sucesso.");
                } catch (\Exception $e) {
                    $this->error("Falha ao criar tabela {$tableId}: " . $e->getMessage());
                    $this->line("Por favor, crie a tabela manualmente no BigQuery com o schema acima e tente novamente.");
                    return 1;
                }
            }
            
            $loadJobConfig = $table->load(fopen(Storage::disk('local')->path($filePath), 'r'))
                ->sourceFormat('CSV')
                ->skipLeadingRows(1); // Pular cabeçalho

            $job = $table->runJob($loadJobConfig);

            // Esperar o job completar (opcional, pode ser longo)
            $this->line('Job de carregamento iniciado. ID: ' . $job->id());
            $this->line('Aguardando conclusão do job... (pode levar alguns minutos)');
            $job->waitUntilComplete();

            if (!$job->isComplete()) {
                throw new \Exception('O job de carregamento do BigQuery não foi concluído.');
            }
            if (isset($job->info()['status']['errorResult'])) {
                $error = $job->info()['status']['errorResult']['message'];
                throw new \Exception("Erro no job do BigQuery: {$error}");
            }

            $this->info("Dados carregados com sucesso para BigQuery: {$datasetId}.{$tableId}");

        } catch (\Google\Cloud\Core\Exception\NotFoundException $e) {
            $this->error("Erro: Dataset ou Tabela não encontrado no BigQuery. " . $e->getMessage());
            $this->line("Verifique se o dataset '{$datasetId}' e a tabela '{$tableId}' existem ou crie-os com o schema correto.");
            return 1;
        } catch (\Exception $e) {
            $this->error("Falha ao carregar dados para o BigQuery: " . $e->getMessage());
            $this->line("Arquivo CSV gerado em: storage/app/{$filePath}");
            $this->line("Você pode tentar o upload manual ou verificar os logs do GCP.");
            return 1;
        }

        $this->info('Exportação e upload para BigQuery concluídos!');
        return 0;
    }
}
 