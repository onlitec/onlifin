
import pg from 'pg';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const { Client } = pg;
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Load env vars
const envPath = path.resolve(__dirname, '../.env');
const envVars = {};
if (fs.existsSync(envPath)) {
    const envContent = fs.readFileSync(envPath, 'utf-8');
    envContent.split('\n').forEach(line => {
        const parts = line.split('=');
        if (parts.length === 2) {
            envVars[parts[0]] = parts[1].trim();
        }
    });
}

const client = new Client({
    user: envVars.POSTGRES_USER || process.env.POSTGRES_USER || 'onlifin',
    host: envVars.POSTGRES_HOST || process.env.POSTGRES_HOST || 'localhost',
    database: envVars.POSTGRES_DB || process.env.POSTGRES_DB || 'onlifin',
    password: envVars.POSTGRES_PASSWORD || process.env.POSTGRES_PASSWORD,
    port: parseInt(envVars.POSTGRES_PORT || process.env.POSTGRES_PORT || '54320'),
});

async function verifyBalanceUpdate() {
    try {
        await client.connect();
        console.log("Connected to database.");

        // 1. Get a user
        const resUser = await client.query('SELECT id FROM profiles LIMIT 1');
        if (resUser.rows.length === 0) {
            console.error("No users found. Cannot create test data.");
            return;
        }
        const userId = resUser.rows[0].id;
        console.log(`Using User ID: ${userId}`);

        // 2. Create a test account
        const accountName = `Test Account ${Date.now()}`;
        const resAccount = await client.query(
            'INSERT INTO accounts (user_id, name, balance) VALUES ($1, $2, 0) RETURNING id, balance',
            [userId, accountName]
        );
        const accountId = resAccount.rows[0].id;
        console.log(`Created Account ID: ${accountId}, Initial Balance: ${resAccount.rows[0].balance}`);

        // 3. Create Income Transaction (+100)
        console.log("Creating Income Transaction (+100)...");
        const resTrans = await client.query(
            "INSERT INTO transactions (user_id, account_id, type, amount, date, description) VALUES ($1, $2, 'income', 100, NOW(), 'Test Income') RETURNING id",
            [userId, accountId]
        );
        const transId = resTrans.rows[0].id;

        // Verify Balance (+100)
        let resBalance = await client.query('SELECT balance FROM accounts WHERE id = $1', [accountId]);
        let balance = parseFloat(resBalance.rows[0].balance);
        console.log(`Balance after Income: ${balance}`);
        if (balance !== 100) throw new Error("Balance should be 100");

        // 4. Update Transaction (Income 100 -> 150)
        console.log("Updating Transaction (+150)...");
        await client.query("UPDATE transactions SET amount = 150 WHERE id = $1", [transId]);

        // Verify Balance (+150)
        resBalance = await client.query('SELECT balance FROM accounts WHERE id = $1', [accountId]);
        balance = parseFloat(resBalance.rows[0].balance);
        console.log(`Balance after Update: ${balance}`);
        if (balance !== 150) throw new Error("Balance should be 150");

        // 5. Delete Transaction
        console.log("Deleting Transaction...");
        await client.query("DELETE FROM transactions WHERE id = $1", [transId]);

        // Verify Balance (0)
        resBalance = await client.query('SELECT balance FROM accounts WHERE id = $1', [accountId]);
        balance = parseFloat(resBalance.rows[0].balance);
        console.log(`Balance after Delete: ${balance}`);
        if (balance !== 0) throw new Error("Balance should be 0");

        console.log("✅ SUCCESS: Balance updates verified correctly!");

        // Cleanup Account
        await client.query("DELETE FROM accounts WHERE id = $1", [accountId]);

    } catch (err) {
        console.error("❌ VERIFICATION FAILED:", err);
        process.exit(1);
    } finally {
        await client.end();
    }
}

verifyBalanceUpdate();
