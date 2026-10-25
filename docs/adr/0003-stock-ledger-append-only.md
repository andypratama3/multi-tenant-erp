# ADR 0003: Stock Ledger Append-Only
**Status:** Accepted
**Date:** 2026-10-25

## Context
Retail inventory requires a full audit trail of every stock change. Direct updates to a quantity field make it impossible to reconstruct history, verify reconciliation, or trace discrepancies.

## Decision
`stock_movements` is an **append-only ledger**. Every stock change (sale, purchase receipt, adjustment, transfer, opname) creates a new row. `stock_balances` is a **projection** of the ledger — a materialized running total maintained by `ApplyStockMovement`. No other code may directly write to `stock_balances` or `stock_movements`.

The database role used by the application has `INSERT` only on `stock_movements`; `UPDATE` and `DELETE` are revoked. Reconciliation jobs verify `SUM(stock_movements.quantity) = stock_balances.quantity` daily.

## Consequences
**Positive:**
- Complete, tamper-evident history of every stock change.
- Reconciliation is straightforward: sum the ledger.
- Time-travel queries: stock level at any point in history = filter movements by date.
- Audit compliance without separate audit tables.

**Negative:**
- `stock_movements` grows indefinitely; requires partitioning by year when exceeding ~20M rows.
- All reads for current stock hit `stock_balances` (fast), but historical queries scan the ledger.
- Corrections require explicit reversal movements, not direct edits.
