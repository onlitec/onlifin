
import pg from 'pg';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const { Client } = pg;
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Constants
const MAX_RETRIES = 30;
const RETRY_DELAY_MS = 2000;

// Config from Environment Variables
const config = {
    user: process.env.POSTGRES_USER || 'onlifin',
    host: process.env.POSTGRES_HOST || 'onlifin-database', // Default service name in Docker Compose
    database: process.env.POSTGRES_DB || 'onlifin',
    password: process.env.POSTGRES_PASSWORD,
    port: parseInt(process.env.POSTGRES_PORT || '5432'),
};

async function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function connectWithRetry() {
    let retries = 0;
    while (retries < MAX_RETRIES) {
        try {
            const client = new Client(config);
            await client.connect();
            console.log("✅ Connected to database successfully.");
            return client;
        } catch (err) {
            retries++;
            console.log(`⏳ Waiting for database... (Attempt ${retries}/${MAX_RETRIES}) - Error: ${err.message}`);
            await sleep(RETRY_DELAY_MS);
        }
    }
    throw new Error(`Failed to connect to database after ${MAX_RETRIES} attempts.`);
}

async function runDeployMigrations() {
    console.log("🚀 Starting deployment migrations...");

    if (!config.password) {
        console.error("❌ Error: POSTGRES_PASSWORD environment variable is not set.");
        process.exit(1);
    }

    let client;
    try {
        client = await connectWithRetry();

        // ---------------------------------------------------------
        // LIST OF MIGRATIONS TO RUN ON DEPLOY
        // Add new safe-to-run-in-prod migrations here
        // ---------------------------------------------------------
        const migrations = [
            '../supabase/migrations/00012_fix_rpc_login.sql'
        ];

        for (const migration of migrations) {
            const migrationPath = path.resolve(__dirname, migration);

            if (fs.existsSync(migrationPath)) {
                console.log(`📜 Applying migration: ${path.basename(migrationPath)}`);
                const sql = fs.readFileSync(migrationPath, 'utf-8');

                await client.query(sql);
                console.log(`✅ Applied successfully.`);
            } else {
                console.warn(`⚠️ Migration file not found: ${migrationPath}`);
            }
        }

        console.log("✨ All deployment migrations completed successfully.");

    } catch (err) {
        console.error("❌ Error running migrations:", err);
        process.exit(1); // Fail the container if migration fails
    } finally {
        if (client) {
            await client.end();
        }
    }
}

runDeployMigrations();
