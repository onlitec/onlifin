-- Migration: Create default people records for all users
-- Description: Ensures every user has a 'Titular (Principal)' record to be managed in the UI

DO $$ 
DECLARE
    user_record RECORD;
BEGIN
    -- Para cada usuário que ainda não tem uma pessoa marcada como padrão
    FOR user_record IN 
        SELECT id FROM auth.users 
        WHERE id NOT IN (SELECT user_id FROM people WHERE is_default = true)
    LOOP
        -- Insere o registro do Titular
        INSERT INTO people (user_id, name, is_default)
        VALUES (user_record.id, 'Titular (Principal)', true);
        
        RAISE NOTICE 'Criado Titular (Principal) para o usuário %', user_record.id;
    END LOOP;
END $$;
