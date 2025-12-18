-- Seed data para Onlifin
-- Este arquivo popula o banco de dados com dados iniciais úteis

-- Nota: O usuário admin já é criado pela migração 00002_create_admin_user_with_password.sql
-- Email: admin@financeiro.com
-- Senha: admin123

-- Inserir categorias padrão de RECEITAS para o admin
-- Primeiro, precisamos obter o user_id do admin
DO $$
DECLARE
    admin_user_id uuid;
BEGIN
    -- Buscar o ID do usuário admin
    SELECT id INTO admin_user_id FROM auth.users WHERE email = 'admin@financeiro.com' LIMIT 1;
    
    -- Se o admin existir, inserir categorias
    IF admin_user_id IS NOT NULL THEN
        -- Categorias de RECEITA
        INSERT INTO public.categories (user_id, name, type, icon, color) VALUES
        (admin_user_id, 'Salário', 'income', 'Briefcase', '#10b981'),
        (admin_user_id, 'Freelance', 'income', 'Code', '#3b82f6'),
        (admin_user_id, 'Investimentos', 'income', 'TrendingUp', '#8b5cf6'),
        (admin_user_id, 'Vendas', 'income', 'ShoppingCart', '#06b6d4'),
        (admin_user_id, 'Outros', 'income', 'DollarSign', '#6b7280')
        ON CONFLICT DO NOTHING;

        -- Categorias de DESPESA
        INSERT INTO public.categories (user_id, name, type, icon, color) VALUES
        (admin_user_id, 'Alimentação', 'expense', 'Utensils', '#ef4444'),
        (admin_user_id, 'Transporte', 'expense', 'Car', '#f97316'),
        (admin_user_id, 'Moradia', 'expense', 'Home', '#eab308'),
        (admin_user_id, 'Saúde', 'expense', 'Heart', '#ec4899'),
        (admin_user_id, 'Educação', 'expense', 'BookOpen', '#8b5cf6'),
        (admin_user_id, 'Lazer', 'expense', 'Film', '#06b6d4'),
        (admin_user_id, 'Compras', 'expense', 'ShoppingBag', '#f43f5e'),
        (admin_user_id, 'Contas', 'expense', 'FileText', '#64748b'),
        (admin_user_id, 'Outros', 'expense', 'MoreHorizontal', '#6b7280')
        ON CONFLICT DO NOTHING;

        -- Inserir uma conta bancária de exemplo
        INSERT INTO public.accounts (user_id, name, bank, agency, account_number, balance, currency) VALUES
        (admin_user_id, 'Conta Corrente Principal', 'Banco Exemplo', '0001', '12345-6', 0, 'BRL')
        ON CONFLICT DO NOTHING;

        -- Inserir um cartão de crédito de exemplo
        INSERT INTO public.cards (user_id, name, card_limit, available_limit, closing_day, due_day) VALUES
        (admin_user_id, 'Cartão Principal', 5000.00, 5000.00, 10, 20)
        ON CONFLICT DO NOTHING;

        RAISE NOTICE 'Dados iniciais inseridos com sucesso para o usuário admin!';
    ELSE
        RAISE NOTICE 'Usuário admin não encontrado. Execute as migrações primeiro.';
    END IF;
END $$;
