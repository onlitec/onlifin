<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'event',
        'description',
        'email_subject',
        'email_content',
        'whatsapp_content',
        'push_title',
        'push_content',
        'push_image',
        'available_variables',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'available_variables' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Processar o template substituindo as variáveis pelos valores reais
     * 
     * @param string $content O conteúdo do template (email, whatsapp, etc)
     * @param array $data Dados para substituir as variáveis
     * @return string
     */
    public function processTemplate(string $content, array $data): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function ($matches) use ($data) {
            $variable = $matches[1];
            
            // Suporte para notação de ponto para acessar propriedades aninhadas
            if (strpos($variable, '.') !== false) {
                $parts = explode('.', $variable);
                $value = $data;
                
                foreach ($parts as $part) {
                    if (isset($value[$part])) {
                        $value = $value[$part];
                    } else {
                        return $matches[0]; // Variável não existe, retorna original
                    }
                }
                
                return $value;
            }
            
            return $data[$variable] ?? $matches[0];
        }, $content);
    }

    /**
     * Obter um template processado para email
     * 
     * @param array $data Dados para substituir as variáveis
     * @return array Assunto e conteúdo processados
     */
    public function getProcessedEmailTemplate(array $data): array
    {
        return [
            'subject' => $this->processTemplate($this->email_subject, $data),
            'content' => $this->processTemplate($this->email_content, $data),
        ];
    }

    /**
     * Obter um template processado para WhatsApp
     * 
     * @param array $data Dados para substituir as variáveis
     * @return string Conteúdo processado
     */
    public function getProcessedWhatsAppTemplate(array $data): string
    {
        return $this->processTemplate($this->whatsapp_content, $data);
    }

    /**
     * Obter um template processado para notificação push
     * 
     * @param array $data Dados para substituir as variáveis
     * @return array Título e conteúdo processados
     */
    public function getProcessedPushTemplate(array $data): array
    {
        return [
            'title' => $this->processTemplate($this->push_title, $data),
            'content' => $this->processTemplate($this->push_content, $data),
            'image' => $this->push_image,
        ];
    }
    
    /**
     * Obter template padrão por tipo
     * 
     * @param string $type Tipo de template (expense, income, etc)
     * @param string|null $event Evento específico do template
     * @return NotificationTemplate|null
     */
    public static function getDefaultByType(string $type, ?string $event = null): ?self
    {
        $query = self::where('type', $type)
            ->where('is_active', true)
            ->where('is_default', true);
            
        if ($event) {
            $query->where('event', $event);
        }
        
        return $query->first();
    }

    /**
     * Criar template padrão para despesa
     */
    public static function createDefaultExpenseTemplate(): self
    {
        return self::create([
            'name' => 'Notificação de Despesa a Vencer',
            'slug' => 'expense-due-date',
            'type' => 'expense',
            'event' => 'due_date',
            'description' => 'Modelo padrão para notificações de despesas a vencer',
            'email_subject' => 'Despesa a vencer: {{ expense.description }}',
            'email_content' => '<p>Olá {{ user.name }},</p>
<p>Você tem uma despesa a vencer {{ days_until_due }} dia(s):</p>
<p><strong>Descrição:</strong> {{ expense.description }}<br>
<strong>Valor:</strong> R$ {{ expense.amount }}<br>
<strong>Vencimento:</strong> {{ expense.due_date }}</p>
<p>Acesse o sistema para ver mais detalhes e efetuar o pagamento.</p>',
            'whatsapp_content' => "*Despesa a vencer*\n\nOlá {{ user.name }},\n\nVocê tem uma despesa a vencer {{ days_until_due }} dia(s):\n\n*Descrição:* {{ expense.description }}\n*Valor:* R$ {{ expense.amount }}\n*Vencimento:* {{ expense.due_date }}\n\nAcesse o sistema para ver mais detalhes.",
            'push_title' => 'Despesa a vencer',
            'push_content' => '{{ expense.description }} - R$ {{ expense.amount }} (vence {{ days_until_due == 0 ? "hoje" : "em " ~ days_until_due ~ " dia(s)" }})',
            'available_variables' => [
                'user.name',
                'user.email',
                'expense.description',
                'expense.amount',
                'expense.due_date',
                'expense.category',
                'days_until_due',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    /**
     * Criar template padrão para receita
     */
    public static function createDefaultIncomeTemplate(): self
    {
        return self::create([
            'name' => 'Notificação de Receita a Receber',
            'slug' => 'income-due-date',
            'type' => 'income',
            'event' => 'due_date',
            'description' => 'Modelo padrão para notificações de receitas a receber',
            'email_subject' => 'Receita a receber: {{ income.description }}',
            'email_content' => '<p>Olá {{ user.name }},</p>
<p>Você tem uma receita a receber {{ days_until_due }} dia(s):</p>
<p><strong>Descrição:</strong> {{ income.description }}<br>
<strong>Valor:</strong> R$ {{ income.amount }}<br>
<strong>Data de recebimento:</strong> {{ income.due_date }}</p>
<p>Acesse o sistema para ver mais detalhes.</p>',
            'whatsapp_content' => "*Receita a receber*\n\nOlá {{ user.name }},\n\nVocê tem uma receita a receber {{ days_until_due }} dia(s):\n\n*Descrição:* {{ income.description }}\n*Valor:* R$ {{ income.amount }}\n*Data de recebimento:* {{ income.due_date }}\n\nAcesse o sistema para ver mais detalhes.",
            'push_title' => 'Receita a receber',
            'push_content' => '{{ income.description }} - R$ {{ income.amount }} (recebe {{ days_until_due == 0 ? "hoje" : "em " ~ days_until_due ~ " dia(s)" }})',
            'available_variables' => [
                'user.name',
                'user.email',
                'income.description',
                'income.amount',
                'income.due_date',
                'income.category',
                'days_until_due',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);
    }
}
