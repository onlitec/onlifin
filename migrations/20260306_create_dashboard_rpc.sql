-- Migration: Criar RPC otimizado para Dashboard
-- Descrição: Stored procedure para buscar todos os dados do dashboard em uma única chamada

CREATE OR REPLACE FUNCTION get_dashboard_data(
    p_user_id UUID,
    p_company_id UUID DEFAULT NULL,
    p_person_id UUID DEFAULT NULL,
    p_start_date DATE DEFAULT NULL,
    p_end_date DATE DEFAULT NULL
)
RETURNS JSON
LANGUAGE plpgsql
AS $$
DECLARE
    v_result JSON;
    v_stats JSON;
    v_category_expenses JSON;
    v_monthly_data JSON;
    v_forecast JSON;
    v_bills_summary JSON;
    v_alerts JSON;
    v_today DATE := CURRENT_DATE;
    v_one_week_from_now DATE := v_today + INTERVAL '7 days';
BEGIN
    -- Estatísticas de transações
    SELECT json_build_object(
        'totalIncome', COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0),
        'totalExpense', COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0),
        'balance', COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0),
        'transactionCount', COUNT(*),
        'averageTransaction', COALESCE(AVG(amount), 0)
    ) INTO v_stats
    FROM transactions
    WHERE user_id = p_user_id
      AND (p_company_id IS NULL OR company_id = p_company_id)
      AND (p_person_id IS NULL OR person_id = p_person_id);

    -- Despesas por categoria (últimos 30 dias)
    SELECT json_agg(
        json_build_object(
            'category', c.name,
            'amount', COALESCE(SUM(t.amount), 0),
            'count', COUNT(t.id)
        )
    ) INTO v_category_expenses
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = p_user_id
      AND t.type = 'expense'
      AND t.date >= v_today - INTERVAL '30 days'
      AND (p_company_id IS NULL OR t.company_id = p_company_id)
      AND (p_person_id IS NULL OR t.person_id = p_person_id)
      AND c.name IS NOT NULL
    GROUP BY c.name
    ORDER BY COALESCE(SUM(t.amount), 0) DESC
    LIMIT 10;

    -- Dados mensais (últimos 6 meses)
    SELECT json_agg(
        json_build_object(
            'month', to_char(DATE_TRUNC('month', date_series.month), 'YYYY-MM'),
            'income', COALESCE(month_data.income, 0),
            'expense', COALESCE(month_data.expense, 0),
            'balance', COALESCE(month_data.income, 0) - COALESCE(month_data.expense, 0)
        )
    ) INTO v_monthly_data
    FROM (
        SELECT generate_series(
            v_today - INTERVAL '5 months',
            v_today,
            INTERVAL '1 month'
        )::DATE AS month
    ) date_series
    LEFT JOIN (
        SELECT 
            DATE_TRUNC('month', date)::DATE as month,
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
        FROM transactions
        WHERE user_id = p_user_id
          AND date >= v_today - INTERVAL '5 months'
          AND (p_company_id IS NULL OR company_id = p_company_id)
          AND (p_person_id IS NULL OR person_id = p_person_id)
        GROUP BY DATE_TRUNC('month', date)::DATE
    ) month_data ON date_series.month = month_data.month
    ORDER BY date_series.month;

    -- Forecast mais recente
    SELECT row_to_json(f) INTO v_forecast
    FROM financial_forecasts f
    WHERE f.user_id = p_user_id
      AND (p_company_id IS NULL OR f.company_id = p_company_id)
      AND (p_person_id IS NULL OR f.person_id = p_person_id)
    ORDER BY f.created_at DESC
    LIMIT 1;

    -- Resumo de contas
    SELECT json_build_object(
        'toPay', json_build_object(
            'total', COALESCE(SUM(CASE WHEN status = 'pending' AND due_date >= v_today THEN amount ELSE 0 END), 0),
            'count', COUNT(CASE WHEN status = 'pending' AND due_date >= v_today THEN 1 END),
            'dueToday', COALESCE(SUM(CASE WHEN status = 'pending' AND due_date = v_today THEN amount ELSE 0 END), 0),
            'overdue', COALESCE(SUM(CASE WHEN status = 'pending' AND due_date < v_today THEN amount ELSE 0 END), 0),
            'dueThisWeek', COALESCE(SUM(CASE WHEN status = 'pending' AND due_date BETWEEN v_today AND v_one_week_from_now THEN amount ELSE 0 END), 0)
        ),
        'toReceive', json_build_object(
            'total', COALESCE(SUM(CASE WHEN status = 'pending' AND due_date >= v_today THEN amount ELSE 0 END), 0),
            'count', COUNT(CASE WHEN status = 'pending' AND due_date >= v_today THEN 1 END),
            'dueToday', COALESCE(SUM(CASE WHEN status = 'pending' AND due_date = v_today THEN amount ELSE 0 END), 0),
            'dueThisWeek', COALESCE(SUM(CASE WHEN status = 'pending' AND due_date BETWEEN v_today AND v_one_week_from_now THEN amount ELSE 0 END), 0)
        )
    ) INTO v_bills_summary
    FROM (
        SELECT * FROM bills_to_pay WHERE user_id = p_user_id
        UNION ALL
        SELECT * FROM bills_to_receive WHERE user_id = p_user_id
    ) all_bills
    WHERE (p_company_id IS NULL OR all_bills.company_id = p_company_id)
      AND (p_person_id IS NULL OR all_bills.person_id = p_person_id);

    -- Alertas não lidos (últimos 10)
    SELECT json_agg(
        json_build_object(
            'id', id,
            'title', title,
            'message', message,
            'type', type,
            'severity', severity,
            'is_read', is_read,
            'created_at', created_at
        )
    ) INTO v_alerts
    FROM notifications
    WHERE user_id = p_user_id
      AND is_read = false
    ORDER BY created_at DESC
    LIMIT 10;

    -- Montar resultado final
    v_result := json_build_object(
        'stats', COALESCE(v_stats, '{}'),
        'categoryExpenses', COALESCE(v_category_expenses, '[]'),
        'monthlyData', COALESCE(v_monthly_data, '[]'),
        'forecast', COALESCE(v_forecast, '{}'),
        'billsSummary', COALESCE(v_bills_summary, '{}'),
        'alerts', COALESCE(v_alerts, '[]')
    );

    RETURN v_result;
END;
$$;

-- Criar índices para otimizar performance
CREATE INDEX IF NOT EXISTS idx_transactions_user_company_person_date ON transactions(user_id, company_id, person_id, date);
CREATE INDEX IF NOT EXISTS idx_transactions_user_type_date ON transactions(user_id, type, date);
CREATE INDEX IF NOT EXISTS idx_bills_user_status_due_date ON bills_to_pay(user_id, status, due_date);
CREATE INDEX IF NOT EXISTS idx_bills_receive_user_status_due_date ON bills_to_receive(user_id, status, due_date);
CREATE INDEX IF NOT EXISTS idx_notifications_user_read_created ON notifications(user_id, is_read, created_at);

-- Comentários
COMMENT ON FUNCTION get_dashboard_data IS 'Função otimizada para buscar todos os dados do dashboard em uma única chamada';
