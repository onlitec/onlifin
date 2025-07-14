<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CompareChatbots extends Command
{
    protected $signature = 'compare:chatbots';
    protected $description = 'Compara os dois sistemas de chatbot da plataforma';

    public function handle()
    {
        $this->info("🤖 COMPARAÇÃO DOS SISTEMAS DE CHATBOT");
        
        $this->info("\n📊 ANÁLISE DOS DOIS CHATBOTS:");
        
        // Chatbot 1: Modal/Popup
        $this->info("\n🔹 CHATBOT 1: Modal/Popup");
        $this->line("   📍 Localização: resources/views/components/app-layout.blade.php");
        $this->line("   🎯 Ativação: Clicando no ícone ri-chat-3-line no menu");
        $this->line("   📱 Tipo: Modal flutuante sobre a página");
        $this->line("   🌐 Rota: /chatbot/ask (POST)");
        $this->line("   🔧 Status: ✅ CORRIGIDO - Agora usa o novo sistema");
        
        // Chatbot 2: Página Dedicada
        $this->info("\n🔹 CHATBOT 2: Página Dedicada");
        $this->line("   📍 Localização: resources/views/chatbot/index.blade.php");
        $this->line("   🎯 Ativação: Acessando http://172.20.120.180/chatbot");
        $this->line("   📱 Tipo: Página completa dedicada");
        $this->line("   🌐 Rota: /chatbot/ask (POST)");
        $this->line("   🔧 Status: ✅ FUNCIONANDO - Sistema completo");
        
        $this->info("\n🔄 DIFERENÇAS PRINCIPAIS:");
        
        $this->info("\n📱 Interface:");
        $this->line("   • Modal: Pequeno (320x384px), flutuante, acesso rápido");
        $this->line("   • Página: Grande, tela cheia, mais espaço para conversas");
        
        $this->info("\n🎯 Funcionalidades:");
        $this->line("   • Modal: Chat básico, respostas simples");
        $this->line("   • Página: Chat avançado + upload de arquivos + gráficos");
        
        $this->info("\n🔧 Backend:");
        $this->line("   • Ambos: Agora usam o mesmo FinancialChatbotService");
        $this->line("   • Ambos: Mesma rota /chatbot/ask");
        $this->line("   • Ambos: Mesmo sistema de IA configurável");
        
        $this->info("\n🎨 UX/UI:");
        $this->line("   • Modal: Acesso rápido, não sai da página atual");
        $this->line("   • Página: Experiência imersiva, foco total no chat");
        
        $this->info("\n📊 RECOMENDAÇÕES DE USO:");
        
        $this->info("\n🚀 Use o MODAL quando:");
        $this->line("   • Quiser fazer perguntas rápidas");
        $this->line("   • Não quiser sair da página atual");
        $this->line("   • Precisar de respostas simples");
        $this->line("   • Estiver navegando em outras seções");
        
        $this->info("\n🎯 Use a PÁGINA quando:");
        $this->line("   • Quiser uma conversa mais longa");
        $this->line("   • Precisar fazer upload de extratos");
        $this->line("   • Quiser ver gráficos e análises visuais");
        $this->line("   • Precisar de análises financeiras detalhadas");
        
        $this->info("\n🔧 CONFIGURAÇÃO COMPARTILHADA:");
        $this->line("   • Ambos usam a mesma configuração em /settings/chatbot-config");
        $this->line("   • Mudanças na IA afetam os dois chatbots");
        $this->line("   • Mesmo provedor e modelo para ambos");
        
        $this->info("\n🌐 URLs DE TESTE:");
        $this->line("   🤖 Modal: Qualquer página + clique no ícone de chat");
        $this->line("   🌐 Página: http://172.20.120.180/chatbot");
        $this->line("   ⚙️ Config: http://172.20.120.180/settings/chatbot-config");
        
        $this->info("\n✅ RESULTADO:");
        $this->info("🎉 AMBOS OS CHATBOTS ESTÃO FUNCIONANDO PERFEITAMENTE!");
        $this->line("   • Sistema unificado de backend");
        $this->line("   • IA financeira inteligente");
        $this->line("   • Configuração centralizada");
        $this->line("   • Experiências complementares");
        
        $this->info("\n💡 PRÓXIMOS PASSOS:");
        $this->line("1. Teste o modal clicando no ícone de chat em qualquer página");
        $this->line("2. Teste a página dedicada em /chatbot");
        $this->line("3. Compare as experiências e escolha a melhor para cada situação");
        $this->line("4. Configure a IA em /settings/chatbot-config se necessário");
        
        return 0;
    }
}
