-- Migration: Setup PF profile and migrate legacy data for ALL users
-- This script creates a default "Pessoa Física" profile named "Você" for every user
-- and associates all orphan legacy data (company_id IS NULL AND person_id IS NULL) to it.

DO $$
DECLARE
    r RECORD;
    v_person_id UUID;
    v_count_accounts INT := 0;
    v_count_transactions INT := 0;
    v_count_cards INT := 0;
    v_count_categories INT := 0;
    v_total_accounts INT := 0;
    v_total_transactions INT := 0;
    v_total_cards INT := 0;
    v_total_categories INT := 0;
BEGIN
    RAISE NOTICE '=== Iniciando Migração Completa de Perfil PF ===';

    FOR r IN (SELECT id, email FROM auth.users) LOOP
        -- 1. Garantir perfil "Você" para cada usuário
        SELECT id INTO v_person_id FROM public.people WHERE user_id = r.id AND is_default = true LIMIT 1;
        
        IF v_person_id IS NULL THEN
            RAISE NOTICE 'Criando perfil "Você" para o usuário % (%)', r.email, r.id;
            INSERT INTO public.people (user_id, name, is_default)
            VALUES (r.id, 'Você', true)
            RETURNING id INTO v_person_id;
        ELSE
            RAISE NOTICE 'Perfil PF existente encontrado para %: %', r.email, v_person_id;
        END IF;

        -- 2. Migrar Contas desse usuário
        UPDATE public.accounts 
        SET person_id = v_person_id
        WHERE user_id = r.id 
          AND company_id IS NULL 
          AND person_id IS NULL;
        GET DIAGNOSTICS v_count_accounts = ROW_COUNT;
        v_total_accounts := v_total_accounts + v_count_accounts;

        -- 3. Migrar Transações desse usuário
        UPDATE public.transactions 
        SET person_id = v_person_id
        WHERE user_id = r.id 
          AND company_id IS NULL 
          AND person_id IS NULL;
        GET DIAGNOSTICS v_count_transactions = ROW_COUNT;
        v_total_transactions := v_total_transactions + v_count_transactions;

        -- 4. Migrar Cartões desse usuário
        UPDATE public.cards 
        SET person_id = v_person_id
        WHERE user_id = r.id 
          AND company_id IS NULL 
          AND person_id IS NULL;
        GET DIAGNOSTICS v_count_cards = ROW_COUNT;
        v_total_cards := v_total_cards + v_count_cards;

        -- 5. Migrar Categorias desse usuário (excluindo as de sistema sem user_id)
        UPDATE public.categories 
        SET person_id = v_person_id
        WHERE user_id = r.id 
          AND company_id IS NULL 
          AND person_id IS NULL;
        GET DIAGNOSTICS v_count_categories = ROW_COUNT;
        v_total_categories := v_total_categories + v_count_categories;

        IF (v_count_accounts + v_count_transactions + v_count_cards + v_count_categories) > 0 THEN
            RAISE NOTICE 'Migrados para %: Contas: %, Transações: %, Cartões: %, Categorias: %', 
                r.email, v_count_accounts, v_count_transactions, v_count_cards, v_count_categories;
        END IF;
    END LOOP;

    -- 6. Marcar categorias sem usuário como sistema para visibilidade global
    UPDATE public.categories 
    SET is_system = true 
    WHERE user_id IS NULL AND is_system = false;
    GET DIAGNOSTICS v_count_categories = ROW_COUNT;

    RAISE NOTICE '=== Resultados Totais da Migração ===';
    RAISE NOTICE 'Total Contas: %', v_total_accounts;
    RAISE NOTICE 'Total Transações: %', v_total_transactions;
    RAISE NOTICE 'Total Cartões: %', v_total_cards;
    RAISE NOTICE 'Total Categorias: %', v_total_categories;
    RAISE NOTICE 'Categorias marcadas como sistema: %', v_count_categories;
    RAISE NOTICE 'Migração concluída com sucesso!';
END $$;
