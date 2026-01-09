
import pg from 'pg';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const { Client } = pg;

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Load env vars (simple approach since we are in a script)
// Assuming .env is in root ../.env
const envPath = path.resolve(__dirname, '../.env');
const envContent = fs.readFileSync(envPath, 'utf-8');
const envVars = {};
envContent.split('\n').forEach(line => {
    const parts = line.split('=');
    if (parts.length === 2) {
        envVars[parts[0]] = parts[1].trim();
    }
});

const client = new Client({
    user: envVars.POSTGRES_USER || 'onlifin',
    host: 'localhost',
    database: envVars.POSTGRES_DB || 'onlifin',
    password: envVars.POSTGRES_PASSWORD,
    port: 54320,
});

async function runMigration() {
    try {
        await client.connect();
        console.log("Connected to database.");

        const migrationPath = path.resolve(__dirname, '../supabase/migrations/00003_add_balance_update_functions.sql');
        const migrationSql = fs.readFileSync(migrationPath, 'utf-8');

        console.log(`Applying migration: ${migrationPath}`);
        await client.query(migrationSql);
        console.log("Migration applied successfully!");

    } catch (err) {
        console.error("Error applying migration:", err);
    } finally {
        await client.end();
    }
}

runMigration();
