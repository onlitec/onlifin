-- Migration: Cleanup duplicate Titular records
-- Description: Ensures only one 'Titular (Principal)' remains per user if duplicates were created

BEGIN;

DELETE FROM people 
WHERE id IN (
    SELECT id 
    FROM (
        SELECT id, 
               ROW_NUMBER() OVER (PARTITION BY user_id, name ORDER BY created_at ASC) as row_num
        FROM people
        WHERE name = 'Titular (Principal)'
    ) t
    WHERE t.row_num > 1
);

COMMIT;
