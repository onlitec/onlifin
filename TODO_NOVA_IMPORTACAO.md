# TODO - Nova Implementação de Importação de Extrato

## Fase 1: Infraestrutura (Banco de Dados e Storage)
- [ ] 1.1 Criar migration para tabela `uploaded_statements`
- [ ] 1.2 Criar bucket no Supabase Storage
- [ ] 1.3 Aplicar migrations
- [ ] 1.4 Adicionar tipos TypeScript
- [ ] 1.5 Adicionar funções de API

## Fase 2: Utilitários e Componentes Base
- [ ] 2.1 Criar `fileUpload.ts`
- [ ] 2.2 Criar `FileUploadArea.tsx`
- [ ] 2.3 Criar `TransactionReviewList.tsx`
- [ ] 2.4 Testar upload e download de arquivos

## Fase 3: Componente Principal e Integração
- [ ] 3.1 Criar `AnalysisResultPopup.tsx`
- [ ] 3.2 Modificar `ChatBot.tsx`
- [ ] 3.3 Integrar todos os componentes
- [ ] 3.4 Testar fluxo completo

## Fase 4: Testes e Refinamentos
- [ ] 4.1 Testar com arquivos CSV
- [ ] 4.2 Testar com arquivos OFX
- [ ] 4.3 Ajustar UI/UX
- [ ] 4.4 Adicionar tratamento de erros
- [ ] 4.5 Otimizar performance

## Notas
- Seguir ordem de implementação
- Testar cada fase antes de avançar
- Manter código limpo e documentado
