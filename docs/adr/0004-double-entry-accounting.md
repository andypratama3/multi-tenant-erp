# ADR 0004: Double-Entry Accounting
**Status:** Accepted
**Date:** 2026-10-25

## Context
The spec requires balance sheet, profit/loss, and cash flow reporting. These financial statements cannot be reliably generated from a simple transactions table or derived cash columns — the data drifts from reality when transactions are voided, reversed, or partially paid.

## Decision
Implement **double-entry bookkeeping**: every financial event produces a `journal_entry` with balanced `journal_lines` (sum of debits = sum of credits). All financial reports (P&L, Balance Sheet, Cash Flow) are generated from journal queries, not from order/expense tables directly.

`PostJournal` is the single gate for creating journals. It validates debit = credit and that the fiscal period is not locked before committing. Corrections are done via reversal journals, never via UPDATE/DELETE.

The database role used by the application has INSERT-only on `journal_entries` and `journal_lines`.

## Consequences
**Positive:**
- Single source of truth for all financial data.
- Balance sheet always balances by construction.
- Full audit trail; period locking prevents retroactive changes.
- Standard accounting reports are SQL aggregations on journals.

**Negative:**
- More complex than a simple income/expense table.
- Every financial event (sale, purchase, expense, adjustment) must generate a journal — more code per event.
- Requires a chart of accounts (COA) to be set up before any transaction.
- Staff and developers must understand basic double-entry concepts.
