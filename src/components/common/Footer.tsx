import React from "react";

const Footer: React.FC = () => {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="bg-card border-t border-border">
      <div className="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {/* Sobre */}
          <div>
            <h3 className="text-lg font-semibold text-foreground mb-4">
              Sobre o OnliFin
            </h3>
            <p className="text-muted-foreground">
              Plataforma de gestão financeira pessoal com assistente de IA para ajudar você a controlar suas finanças de forma inteligente.
            </p>
          </div>

          {/* Recursos */}
          <div>
            <h3 className="text-lg font-semibold text-foreground mb-4">
              Recursos
            </h3>
            <div className="text-muted-foreground space-y-2">
              <p>• Gestão de contas e cartões</p>
              <p>• Controle de receitas e despesas</p>
              <p>• Importação de extratos</p>
              <p>• Assistente de IA contextual</p>
            </div>
          </div>

          {/* Suporte */}
          <div>
            <h3 className="text-lg font-semibold text-foreground mb-4">
              Suporte
            </h3>
            <div className="text-muted-foreground space-y-2">
              <p>Documentação completa disponível</p>
              <p>Assistente de IA para ajuda</p>
              <p>Painel administrativo</p>
            </div>
          </div>
        </div>

        {/* Copyright */}
        <div className="mt-8 pt-8 border-t border-border text-center text-muted-foreground">
          <p>{currentYear} OnliFin</p>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
