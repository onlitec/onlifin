-- ===========================================
-- 03 - Dados Iniciais
-- ===========================================

-- Criar usuário admin
DO $$
DECLARE
    v_admin_id uuid;
BEGIN
    -- Registrar usuário admin
    v_admin_id := auth.register('admin@onlifin.com', '*M3a74g20M');
    
    IF v_admin_id IS NOT NULL THEN
        -- Criar perfil admin
        INSERT INTO profiles (id, username, full_name, role)
        VALUES (v_admin_id, 'admin', 'Administrador', 'admin'::user_role);
        
        -- Categorias de receita
        INSERT INTO categories (user_id, name, type, icon, color) VALUES
        (v_admin_id, 'Salário', 'income', 'Briefcase', '#10b981'),
        (v_admin_id, 'Freelance', 'income', 'Code', '#3b82f6'),
        (v_admin_id, 'Investimentos', 'income', 'TrendingUp', '#8b5cf6'),
        (v_admin_id, 'Vendas', 'income', 'ShoppingCart', '#06b6d4'),
        (v_admin_id, 'Outros', 'income', 'DollarSign', '#6b7280');
        
        -- Categorias de despesa
        INSERT INTO categories (user_id, name, type, icon, color) VALUES
        (v_admin_id, 'Alimentação', 'expense', 'Utensils', '#ef4444'),
        (v_admin_id, 'Transporte', 'expense', 'Car', '#f97316'),
        (v_admin_id, 'Moradia', 'expense', 'Home', '#eab308'),
        (v_admin_id, 'Saúde', 'expense', 'Heart', '#ec4899'),
        (v_admin_id, 'Educação', 'expense', 'BookOpen', '#8b5cf6'),
        (v_admin_id, 'Lazer', 'expense', 'Film', '#06b6d4'),
        (v_admin_id, 'Compras', 'expense', 'ShoppingBag', '#f43f5e'),
        (v_admin_id, 'Contas', 'expense', 'FileText', '#64748b'),
        (v_admin_id, 'Outros', 'expense', 'MoreHorizontal', '#6b7280');
        
        -- Conta bancária de exemplo
        INSERT INTO accounts (user_id, name, bank, agency, account_number, balance, currency)
        VALUES (v_admin_id, 'Conta Corrente Principal', 'Banco Exemplo', '0001', '12345-6', 0, 'BRL');
        
        -- Cartão de crédito de exemplo
        INSERT INTO cards (user_id, name, card_limit, available_limit, closing_day, due_day)
        VALUES (v_admin_id, 'Cartão Principal', 5000.00, 5000.00, 10, 20);
        
        -- Configuração IA para Ollama
        INSERT INTO ai_configurations (model_name, endpoint, permission_level, can_write_transactions, is_active)
        VALUES ('llama3.2:3b', 'http://ollama:11434', 'read_aggregated', false, true);
        
        RAISE NOTICE 'Usuário admin criado com sucesso!';
    ELSE
        RAISE NOTICE 'Falha ao criar usuário admin ou já existe.';
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

-- Dados iniciais inseridos com sucesso
