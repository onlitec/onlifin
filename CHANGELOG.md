# Changelog


## [1.18.0] - 2026-02-10

### feat: implement person registration for PF (people management, contexts, filters)

---



## [1.17.0] - 2026-01-31

### feat: implement person registration for PF (people management, contexts, filters)

---



## [1.16.1] - 2026-01-31

### chore: sync docs and sidebar updates across branches

---



## [1.16.0] - 2026-01-30

### feat(sidebar): display Nome Fantasia prominently in header and selector

---



## [1.15.0] - 2026-01-30

### feat(sidebar): implement collapsible PF/PJ sections and company selector

---



## [1.14.0] - 2026-01-30

### feat: implement PF/PJ module separation and UI isolation

---



## [1.13.0] - 2026-01-15

### feat(import): add real-time progress bar and activity log during AI analysis

---



## [1.12.2] - 2026-01-15

### fix(import): auto-select first account and fix missing recurring_schedules table

---



## [1.12.1] - 2026-01-15

### style(ui): update headers to full-width and fix lint errors

---



## [1.12.0] - 2026-01-15

### feat(accounts): convert from cards to compact list layout

---



## [1.11.1] - 2026-01-15

### fix(ui): increase border opacity for better visibility

---



## [1.11.0] - 2026-01-15

### feat(ui): add colored borders to status cards and lists

---



## [1.10.0] - 2026-01-15

### feat(bills): show associated bank account in bills list

---



## [1.9.1] - 2026-01-15

### fix(dashboard): exclude internal transfers from income/expense totals

---



## [1.9.0] - 2026-01-15

### feat(dashboard): add pending bills to receive card

---



## [1.8.0] - 2026-01-15

### feat: add automatic backup before running migrations

---



## [1.6.2] - 2026-01-09

### Merge master into main (resolve version conflicts and bump to 1.6.1)

---



## [1.6.1] - 2026-01-09

### feat(ui): display dynamic app version in header menu
### chore(scripts): update verify-balance.js for docker execution

---



## [1.5.0] - 2026-01-09

### feat(ui): display dynamic app version in header menu

---



## [1.4.0] - 2026-01-09

### feat(ci): automate db migrations with migration service

---



## [1.3.3] - 2026-01-09

### fix(docker): include RPC login fix migration in postgres image

---



## [1.3.2] - 2026-01-09

### fix(auth): correct login RPC function version and update app version display

---



## [1.3.1] - 2026-01-09

### Manual release

---



## [1.3.0] - 2026-01-09

### feat: add version display in footer

---



## [1.2.0] - 2026-01-09

### feat: add fully automated release workflow

---


## [1.1.1] - 2026-01-09

### Adiciona scripts de automação de releases e GitHub Action

---

## [1.1.0] - 2026-01-09 - Automatic Balance Updates

### Added
- **Automatic Account Balance Updates**: Account balances are now automatically updated when transactions are created, modified, or deleted.
  - Database trigger `trigger_update_account_balance` on `transactions` table.
  - Function `update_account_balance_on_transaction()` handles balance logic.
  - Function `recalculate_account_balance(account_uuid)` available to fix discrepancies.
  - Function `recalculate_all_account_balances(user_uuid)` to recalculate all accounts for a user.

### Technical Details
- Migration: `supabase/migrations/00003_add_balance_update_functions.sql`
- Income transactions **add** to balance.
- Expense transactions **subtract** from balance.
- Updates correctly reverse old values before applying new ones.
- Deletions correctly reverse the transaction effect.

### Verification
- Added `scripts/verify-balance.js` for automated testing.
- All INSERT, UPDATE, DELETE scenarios verified.

---

## [2025-12-08] - Menu Reorganization and Bug Fixes

### Changed
- **Menu Structure**: Moved "Contas a Pagar" and "Contas a Receber" to be submenus under "Transações"
  - Before: Standalone menu items
  - After: Nested under Transações menu alongside Importar Extrato, Importar, and Conciliação

### Fixed
- **Database Query Error**: Fixed "Could not embed because more than one relationship was found for 'transactions' and 'accounts'" error
  - Root cause: The transactions table has two foreign keys to accounts table:
    - `account_id` (source account)
    - `transfer_destination_account_id` (destination account for transfers)
  - Solution: Explicitly specified the foreign key in Supabase queries using `accounts!account_id(*)`
  - Affected methods:
    - `transactionsApi.getTransactions()`
    - `transactionsApi.getTransaction()`

### Technical Details

#### Menu Structure (src/routes.tsx)
```typescript
{
  name: 'Transações',
  path: '/transactions',
  element: <Transactions />,
  visible: true,
  children: [
    {
      name: 'Contas a Pagar',
      path: '/bills-to-pay',
      element: <BillsToPay />,
      visible: true
    },
    {
      name: 'Contas a Receber',
      path: '/bills-to-receive',
      element: <BillsToReceive />,
      visible: true
    },
    // ... other submenus
  ]
}
```

#### Database Query Fix (src/db/api.ts)
```typescript
// Before (caused error):
.select(`
  *,
  category:categories(*),
  account:accounts(*),  // ❌ Ambiguous - which foreign key?
  card:cards(*)
`)

// After (fixed):
.select(`
  *,
  category:categories(*),
  account:accounts!account_id(*),  // ✅ Explicit foreign key
  card:cards(*)
`)
```

### Files Modified
- `src/routes.tsx` - Menu structure reorganization
- `src/db/api.ts` - Fixed ambiguous foreign key references

### Testing
- ✅ Linter passes with no errors
- ✅ TypeScript compilation successful
- ✅ Menu navigation structure updated
- ✅ Database queries fixed

---

## Previous Changes

See TODO_AI_FORECAST.md for complete AI Financial Forecasting System implementation details.
