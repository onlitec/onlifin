# Changelog

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
