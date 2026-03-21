-- ===========================================
-- 03 - Dados Iniciais
-- ===========================================
-- Usuario admin padrao: onlifinadmin / Onlifin@2025!

-- Criar usuário admin
DO $$
DECLARE
    v_admin_id uuid;
BEGIN
    -- Registrar usuário admin (email: onlifinadmin@miaoda.com)
    v_admin_id := auth.register('onlifinadmin@miaoda.com', 'Onlifin@2025!');
    
    IF v_admin_id IS NOT NULL THEN
        -- Criar perfil admin
        INSERT INTO profiles (id, username, full_name, role)
        VALUES (v_admin_id, 'onlifinadmin', 'Administrador Onlifin', 'admin'::user_role)
        ON CONFLICT (id) DO NOTHING;
        
        -- Categorias pessoais adicionais do admin
        INSERT INTO categories (user_id, name, type, icon, color) VALUES
        (v_admin_id, 'Vendas', 'income', 'ShoppingCart', '#06b6d4'),
        (v_admin_id, 'Outros', 'income', 'DollarSign', '#6b7280')
        ON CONFLICT DO NOTHING;

        -- Categoria pessoal adicional do admin
        INSERT INTO categories (user_id, name, type, icon, color) VALUES
        (v_admin_id, 'Outros', 'expense', 'MoreHorizontal', '#6b7280')
        ON CONFLICT DO NOTHING;
        
        -- Conta bancária de exemplo
        INSERT INTO accounts (user_id, name, bank, agency, account_number, balance, currency)
        VALUES (v_admin_id, 'Conta Corrente Principal', 'Banco Exemplo', '0001', '12345-6', 0, 'BRL');
        
        -- Cartão de crédito de exemplo
        INSERT INTO cards (user_id, name, card_limit, available_limit, closing_day, due_day)
        VALUES (v_admin_id, 'Cartão Principal', 5000.00, 5000.00, 10, 20);
        
        -- Configuração IA para Ollama
        INSERT INTO ai_configurations (model_name, endpoint, permission_level, can_write_transactions, is_active)
        VALUES ('qwen2.5:0.5b', 'http://onlifin-ollama:11434', 'read_aggregated', false, true)
        ON CONFLICT DO NOTHING;
        
        RAISE NOTICE '✅ Usuário admin criado com sucesso!';
        RAISE NOTICE '   Usuario: onlifinadmin';
        RAISE NOTICE '   Senha: Onlifin@2025!';
    ELSE
        RAISE NOTICE '⚠️ Usuário admin já existe ou falha ao criar.';
    END IF;
END;
$$;

-- Categorias do sistema (visíveis para todos)
INSERT INTO categories (user_id, name, type, icon, color) VALUES
(NULL, 'Salário', 'income', '💰', '#27AE60'),
(NULL, 'Freelance', 'income', '💼', '#27AE60'),
(NULL, 'Investimentos', 'income', '📈', '#27AE60'),
(NULL, 'Outros Rendimentos', 'income', '💵', '#27AE60'),
(NULL, 'Alimentação', 'expense', '🍔', '#E74C3C'),
(NULL, 'Transporte', 'expense', '🚗', '#E74C3C'),
(NULL, 'Moradia', 'expense', '🏠', '#E74C3C'),
(NULL, 'Saúde', 'expense', '🏥', '#E74C3C'),
(NULL, 'Educação', 'expense', '📚', '#E74C3C'),
(NULL, 'Lazer', 'expense', '🎮', '#E74C3C'),
(NULL, 'Compras', 'expense', '🛒', '#E74C3C'),
(NULL, 'Contas', 'expense', '📄', '#E74C3C'),
(NULL, 'Outros Gastos', 'expense', '💸', '#E74C3C')
ON CONFLICT DO NOTHING;

-- ===========================================
-- CREDENCIAIS PADRAO
-- ===========================================
-- Usuario: onlifinadmin
-- Senha: Onlifin@2025!
-- ===========================================
