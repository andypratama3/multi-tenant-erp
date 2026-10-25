# ADR 0005: Moving Average Costing (per Store per Product)
**Status:** Accepted
**Date:** 2026-10-25

## Context
Cost of Goods Sold (COGS/HPP) must be calculated for each item sold. FIFO and weighted-average are the two common methods for retail. The system also handles perishable goods that need FEFO (First Expired, First Out) lot tracking.

## Decision
Use **moving average cost** per `(store_id, product_id)` pair. The average unit cost is recalculated on every incoming stock movement (purchase receipt, positive adjustment) using:

```
new_avg = (current_qty × current_avg + incoming_qty × incoming_cost) / (current_qty + incoming_qty)
```

For perishable products (`is_perishable = true`), lot tracking (`stock_lots`) provides FEFO lot selection for outbound movements. The lot system tracks quantity and expiry, but costing remains moving average (not lot-specific FIFO cost).

Rounding: `avg_cost` stored as `DECIMAL(18,4)`, HPP per line = `round(qty × avg_cost, 2)`. Rounding differences go to a rounding account.

## Consequences
**Positive:**
- Simple to implement and explain to store owners.
- Automatically smooths cost fluctuations from price changes.
- Sufficient accuracy for SME retail reporting.
- FEFO lot selection prevents expired goods from being sold last.

**Negative:**
- Not FIFO-exact; cost per lot is not tracked separately.
- Moving average can be distorted by returns at non-original cost (mitigated by using original avg_cost for returns).
- Requires `avg_cost > 0` guard before division in `ApplyStockMovement`.
