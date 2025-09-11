-- ========================================================================
-- ONLIFIN - OTIMIZAÇÕES DE BANCO DE DADOS PARA PERFORMANCE
-- ========================================================================
-- 
-- Este arquivo contém otimizações de banco de dados para melhorar
-- a performance das consultas e operações do sistema.
--
-- ========================================================================

-- ========================================================================
-- ÍNDICES PARA OTIMIZAÇÃO DE CONSULTAS
-- ========================================================================

-- Índices para tabela transactions
CREATE INDEX IF NOT EXISTS idx_transactions_user_id ON transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_transactions_date ON transactions(date);
CREATE INDEX IF NOT EXISTS idx_transactions_type ON transactions(type);
CREATE INDEX IF NOT EXISTS idx_transactions_status ON transactions(status);
CREATE INDEX IF NOT EXISTS idx_transactions_category_id ON transactions(category_id);
CREATE INDEX IF NOT EXISTS idx_transactions_account_id ON transactions(account_id);
CREATE INDEX IF NOT EXISTS idx_transactions_company_id ON transactions(company_id);

-- Índice composto para consultas frequentes
CREATE INDEX IF NOT EXISTS idx_transactions_user_date_type ON transactions(user_id, date, type);
CREATE INDEX IF NOT EXISTS idx_transactions_user_status_date ON transactions(user_id, status, date);
CREATE INDEX IF NOT EXISTS idx_transactions_user_category_date ON transactions(user_id, category_id, date);

-- Índices para tabela accounts
CREATE INDEX IF NOT EXISTS idx_accounts_user_id ON accounts(user_id);
CREATE INDEX IF NOT EXISTS idx_accounts_group_id ON accounts(group_id);
CREATE INDEX IF NOT EXISTS idx_accounts_active ON accounts(active);

-- Índices para tabela categories
CREATE INDEX IF NOT EXISTS idx_categories_user_id ON categories(user_id);
CREATE INDEX IF NOT EXISTS idx_categories_type ON categories(type);
CREATE INDEX IF NOT EXISTS idx_categories_user_type ON categories(user_id, type);

-- Índices para tabela users
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_is_admin ON users(is_admin);
CREATE INDEX IF NOT EXISTS idx_users_is_active ON users(is_active);

-- Índices para tabela audit_logs
CREATE INDEX IF NOT EXISTS idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at ON audit_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_audit_logs_event ON audit_logs(event);

-- ========================================================================
-- CONFIGURAÇÕES DE PERFORMANCE DO MYSQL
-- ========================================================================

-- Configurações de buffer pool
SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB
SET GLOBAL innodb_buffer_pool_instances = 4;

-- Configurações de log
SET GLOBAL innodb_log_file_size = 268435456; -- 256MB
SET GLOBAL innodb_log_buffer_size = 16777216; -- 16MB

-- Configurações de cache de consultas
SET GLOBAL query_cache_size = 134217728; -- 128MB
SET GLOBAL query_cache_type = 1;

-- Configurações de conexões
SET GLOBAL max_connections = 200;
SET GLOBAL max_connect_errors = 1000;

-- Configurações de timeout
SET GLOBAL wait_timeout = 28800;
SET GLOBAL interactive_timeout = 28800;

-- ========================================================================
-- VIEWS PARA CONSULTAS COMPLEXAS
-- ========================================================================

-- View para resumo financeiro por usuário
CREATE OR REPLACE VIEW user_financial_summary AS
SELECT 
    u.id as user_id,
    u.name as user_name,
    u.email,
    COUNT(t.id) as total_transactions,
    SUM(CASE WHEN t.type = 'income' AND t.status = 'paid' THEN t.amount ELSE 0 END) as total_income,
    SUM(CASE WHEN t.type = 'expense' AND t.status = 'paid' THEN t.amount ELSE 0 END) as total_expenses,
    SUM(CASE WHEN t.type = 'income' AND t.status = 'paid' THEN t.amount ELSE 0 END) - 
    SUM(CASE WHEN t.type = 'expense' AND t.status = 'paid' THEN t.amount ELSE 0 END) as net_balance,
    COUNT(CASE WHEN t.status = 'pending' THEN 1 END) as pending_transactions
FROM users u
LEFT JOIN transactions t ON u.id = t.user_id
GROUP BY u.id, u.name, u.email;

-- View para relatório de categorias
CREATE OR REPLACE VIEW category_report AS
SELECT 
    c.id as category_id,
    c.name as category_name,
    c.type as category_type,
    c.user_id,
    COUNT(t.id) as transaction_count,
    SUM(CASE WHEN t.status = 'paid' THEN t.amount ELSE 0 END) as total_amount,
    AVG(CASE WHEN t.status = 'paid' THEN t.amount ELSE 0 END) as average_amount,
    MAX(t.date) as last_transaction_date
FROM categories c
LEFT JOIN transactions t ON c.id = t.category_id
GROUP BY c.id, c.name, c.type, c.user_id;

-- View para relatório de contas
CREATE OR REPLACE VIEW account_report AS
SELECT 
    a.id as account_id,
    a.name as account_name,
    a.type as account_type,
    a.user_id,
    a.initial_balance,
    COUNT(t.id) as transaction_count,
    SUM(CASE WHEN t.type = 'income' AND t.status = 'paid' THEN t.amount ELSE 0 END) as total_income,
    SUM(CASE WHEN t.type = 'expense' AND t.status = 'paid' THEN t.amount ELSE 0 END) as total_expenses,
    a.initial_balance + 
    SUM(CASE WHEN t.type = 'income' AND t.status = 'paid' THEN t.amount ELSE 0 END) - 
    SUM(CASE WHEN t.type = 'expense' AND t.status = 'paid' THEN t.amount ELSE 0 END) as current_balance
FROM accounts a
LEFT JOIN transactions t ON a.id = t.account_id
GROUP BY a.id, a.name, a.type, a.user_id, a.initial_balance;

-- ========================================================================
-- PROCEDURES PARA OPERAÇÕES COMPLEXAS
-- ========================================================================

-- Procedure para recalcular saldo de conta
DELIMITER //
CREATE PROCEDURE RecalculateAccountBalance(IN account_id INT)
BEGIN
    DECLARE initial_balance DECIMAL(15,2);
    DECLARE total_income DECIMAL(15,2);
    DECLARE total_expenses DECIMAL(15,2);
    DECLARE current_balance DECIMAL(15,2);
    
    -- Obter saldo inicial
    SELECT COALESCE(initial_balance, 0) INTO initial_balance
    FROM accounts WHERE id = account_id;
    
    -- Calcular receitas
    SELECT COALESCE(SUM(amount), 0) INTO total_income
    FROM transactions 
    WHERE account_id = account_id AND type = 'income' AND status = 'paid';
    
    -- Calcular despesas
    SELECT COALESCE(SUM(amount), 0) INTO total_expenses
    FROM transactions 
    WHERE account_id = account_id AND type = 'expense' AND status = 'paid';
    
    -- Calcular saldo atual
    SET current_balance = initial_balance + (total_income / 100) - (total_expenses / 100);
    
    -- Atualizar saldo na tabela
    UPDATE accounts 
    SET current_balance = current_balance 
    WHERE id = account_id;
    
    SELECT current_balance as new_balance;
END //
DELIMITER ;

-- Procedure para limpeza de dados antigos
DELIMITER //
CREATE PROCEDURE CleanupOldData(IN days_to_keep INT)
BEGIN
    -- Limpar logs de auditoria antigos
    DELETE FROM audit_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    
    -- Limpar sessões expiradas
    DELETE FROM sessions 
    WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL days_to_keep DAY));
    
    -- Limpar tokens de API expirados
    DELETE FROM personal_access_tokens 
    WHERE expires_at < NOW();
    
    SELECT 'Cleanup completed' as result;
END //
DELIMITER ;

-- ========================================================================
-- TRIGGERS PARA MANUTENÇÃO AUTOMÁTICA
-- ========================================================================

-- Trigger para atualizar saldo da conta quando transação é inserida
DELIMITER //
CREATE TRIGGER update_account_balance_insert
AFTER INSERT ON transactions
FOR EACH ROW
BEGIN
    IF NEW.status = 'paid' THEN
        CALL RecalculateAccountBalance(NEW.account_id);
    END IF;
END //
DELIMITER ;

-- Trigger para atualizar saldo da conta quando transação é atualizada
DELIMITER //
CREATE TRIGGER update_account_balance_update
AFTER UPDATE ON transactions
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status OR OLD.amount != NEW.amount OR OLD.account_id != NEW.account_id THEN
        -- Recalcular saldo da conta antiga se mudou
        IF OLD.account_id != NEW.account_id THEN
            CALL RecalculateAccountBalance(OLD.account_id);
        END IF;
        
        -- Recalcular saldo da conta nova
        CALL RecalculateAccountBalance(NEW.account_id);
    END IF;
END //
DELIMITER ;

-- ========================================================================
-- CONFIGURAÇÕES DE MANUTENÇÃO
-- ========================================================================

-- Event para limpeza automática de dados antigos
CREATE EVENT IF NOT EXISTS cleanup_old_data
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
  CALL CleanupOldData(90); -- Manter dados dos últimos 90 dias

-- Event para otimização de tabelas
CREATE EVENT IF NOT EXISTS optimize_tables
ON SCHEDULE EVERY 1 WEEK
STARTS CURRENT_TIMESTAMP
DO
  OPTIMIZE TABLE transactions, accounts, categories, users, audit_logs;

-- ========================================================================
-- CONFIGURAÇÕES DE MONITORAMENTO
-- ========================================================================

-- Habilitar log de consultas lentas
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2; -- Log queries que demoram mais de 2 segundos
SET GLOBAL slow_query_log_file = '/var/log/mysql/slow.log';

-- Habilitar log de queries não indexadas
SET GLOBAL log_queries_not_using_indexes = 'ON';
