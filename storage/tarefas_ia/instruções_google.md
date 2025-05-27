"Você é um engenheiro de software sênior, especialista em arquitetura de microsserviços, desenvolvimento web com Laravel/PHP e integração com serviços de inteligência artificial em nuvem, especificamente no Google Cloud Platform (GCP). Sua próxima tarefa é guiar a integração completa de uma plataforma financeira em Laravel com os serviços GCP previamente configurados.

Contexto da Configuração Atual:

Aplicação Principal: Desenvolvida em Laravel (PHP).
Credenciais GCP: O arquivo google-cloud-credentials.json (Service Account Key) está salvo no disco, dentro do diretório storage/app do projeto Laravel, e devera ser devidamente adicionado ao .gitignore.
Serviços GCP Configurados:
Cloud Storage: Um bucket GCS existe para armazenamento temporário de uploads (ex: financial-app-data-onlifin).
Service Account: laravel-integrator-sa criada com as permissões necessárias para todos os serviços a seguir.
Dialogflow ES: Um agente de chatbot foi criado e configurado para interação em português.
Document AI: Um processador MyFormParser (ID: 44a9676ffd9416a3) foi criado e está localizado na região multi-regional us (principalmente us-central1 para chamadas).
Cloud Vision API: Habilitada.
Natural Language API: Habilitada.
AutoML Natural Language: Habilitada (assumimos que um modelo de categorização personalizado será treinado e seu ID será usado para inferência).
BigQuery: Um dataset (financial_data_prod) foi criado.
BigQuery ML: Habilitado.
Secret Manager: Habilitado.
Banco de Dados: Laravel utilizará um banco de dados relacional padrão (ex: MySQL/PostgreSQL) localmente no servidor ou em um serviço como Google Cloud SQL.
Objetivo:

Fornecer um guia detalhado e prático para a integração passo a passo, incluindo exemplos de código PHP/Laravel, para cada uma das funcionalidades solicitadas, garantindo que o google-cloud-credentials.json seja carregado de forma correta e segura, e que as chamadas às APIs do GCP considerem a região correta do Document AI.

Detalhes da Integração Solicitada:

Autenticação e Configuração dos Clientes GCP no Laravel:

Instruções para a instalação dos pacotes PHP SDK do Google Cloud via Composer (reforçando os que já foram mencionados, mas confirmando que estão todos).
Exemplo de como o Laravel deve carregar as credenciais do google-cloud-credentials.json a partir de storage/app.
Configuração de um Service Provider (ex: GcpServiceProvider) para inicializar e gerenciar as instâncias dos clientes GCP (Dialogflow, Storage, Document AI, Vision, Natural Language, etc.) para injeção de dependência.
Recomendação para uso do Google Secret Manager para produção (como alternativa ao storage/app para a chave JSON), incluindo um exemplo básico de acesso a um segredo.
Implementação do Chatbot com Dialogflow (Front-end e Back-end):

Front-end (HTML/JS): Exemplo de um widget de chat simples (HTML e JavaScript) que permita ao usuário digitar mensagens e selecionar e anexar arquivos (comprovantes, extratos, planilhas). Este widget deve enviar os dados (mensagem e arquivo) para o backend Laravel via AJAX (usando FormData).
Back-end (Laravel Controller): Exemplo de um controller (ChatbotController) que:
Receba as mensagens e arquivos do front-end.
Interaja com o Dialogflow (usando SessionsClient).
Envie as mensagens do usuário para o Dialogflow (detectIntent).
Receba e formate a resposta do Dialogflow para o front-end.
Importantíssimo: Se houver um arquivo anexado, o controller deve:
Validar o arquivo (tamanho, tipo MIME).
Fazer o upload seguro do arquivo para o Cloud Storage (com StorageClient e ACLs privadas).
Disparar um Laravel Job (fila) para o processamento assíncrono do arquivo, passando o caminho do arquivo no GCS e o user_id.
Gestão de Arquivos no Cloud Storage:

Detalhes sobre a configuração do bucket GCS (via variáveis de ambiente).
Código PHP para upload (StorageClient) e para download/leitura de arquivos dentro do Job, garantindo segurança (ACLs) e eficiência.
Estratégias para gerenciamento do ciclo de vida dos arquivos (ex: exclusão após processamento).
Processamento de Arquivos com Document AI, Cloud Vision e NLP:

Laravel Job (ProcessUploadedFinancialFile): Detalhamento da classe Job que foi mencionada anteriormente.
Download/Leitura do Arquivo: Como o Job acessa o arquivo do Cloud Storage.
Lógica de Seleção de API: Como determinar (baseado no tipo MIME ou talvez no nome do arquivo indicado pelo usuário via chatbot) qual API usar: Document AI (para PDFs/Imagens estruturadas), Cloud Vision (para OCR genérico em imagens) ou Laravel Excel (para planilhas).
Document AI Implantação:
Como chamar o processador MyFormParser (ID: 44a9676ffd9416a3).
CRÍTICO: Como especificar a região correta (us ou us-central1) para a chamada ao Document AI, garantindo que o resto da aplicação continue usando southamerica-east1 para armazenamento final.
Exemplo de parsing da resposta do Document AI para extrair dados chave (data, descrição, valor).
Cloud Vision API: Exemplo de como usar VisionClient para OCR de texto genérico em imagens de cupom fiscal.
Processamento de Planilhas (CSV/Excel): Reafirmar o uso de Maatwebsite/Excel e o Job para processar linhas de transação.
Categorização com NLP/AutoML:
Como usar LanguageServiceClient para NLP genérico ou PredictionServiceClient para chamar o modelo AutoML personalizado (assumindo que o model_id será fornecido).
Lógica para atribuir categorias às transações extraídas.
Cadastro Dinâmico de Transações no Banco de Dados Laravel:

Exemplo do modelo Transaction (Eloquent) com os campos relevantes.
Lógica dentro do Job para persistir as transações extraídas e categorizadas no banco de dados, associando-as ao user_id.
Considerações sobre a normalização dos dados.
Previsões Financeiras com BigQuery ML:

Como seus dados de transactions serão exportados/sincronizados com o BigQuery.
Exemplo de um Laravel Service (FinancialPredictionService) que execute consultas no BigQuery (usando BigQueryClient) para invocar modelos de BigQuery ML (ex: ML.FORECAST) e buscar previsões (gastos futuros, fluxo de caixa).
Como formatar os resultados para serem consumidos pela interface.
Geração de Gráficos Interativos (Back-end e Front-end):

Back-end (Laravel): Como o controller busca os dados do banco de dados (ou BigQuery) e os formata em um JSON otimizado para Chart.js/Google Charts (reafirmando o ChartResource).
Front-end (HTML/JS): Exemplo de como o JavaScript recebe esses dados e renderiza um gráfico interativo (ex: gastos por categoria), possivelmente via uma função no widget do chatbot.
Opção de como o chatbot pode "exibir" um gráfico (ex: link para uma página, ou JSON para renderização no próprio chat).
Considerações de Segurança e LGPD na Integração:

Reforçar a proteção dos dados sensíveis do usuário durante todo o ciclo: upload, armazenamento temporário, processamento (Document AI em us vs. armazenamento final em southamerica-east1).
Criptografia de arquivos (laravel-encryption ou GCS KMS).
Gerenciamento de permissões (IAM).
Consentimento do usuário para processamento de dados financeiros.
Tratamento de Erros e Logs:

Melhores práticas para try-catch em chamadas de API do GCP.
Registro de logs detalhados (Laravel Log facade, GCP Cloud Logging) para depuração e monitoramento do processamento e interações do chatbot.
Otimização e Performance:

Reforçar o uso de Laravel Queues para todos os processos pesados (upload, processamento de IA, inserção massiva de DB).
Considerações sobre latência de chamadas cross-region para Document AI e estratégias de mitigação.
Gerenciamento eficiente de recursos de banco de dados e Redis para filas.
O output esperado da IA deve ser um guia prático, com seções muito claras para cada ponto, e exemplos de código que possam ser adaptados e aplicados no projeto Laravel existente. Cada exemplo de código deve ser auto-contido e demonstrar a funcionalidade específica, com comentários explicativos."