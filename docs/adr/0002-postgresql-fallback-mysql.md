# ADR 0002: PostgreSQL Primary, MySQL as Fallback
**Status:** Accepted (Fallback Active)
**Date:** 2026-10-25

## Context
The spec (v2.2.0) targets PostgreSQL 17 for its CHECK constraints, partial indexes, transactional DDL, and deferrable constraints (useful for double-entry journal balance validation). However, the development and production environment has MySQL 26.10.0 (Homebrew) available and PostgreSQL is not installed.

## Decision
Use **MySQL 26.10.0** (InnoDB, utf8mb4) as the active database for this deployment. This is the ADR 002 fallback path. All migrations use Laravel's database-agnostic syntax. The following PostgreSQL-specific features require alternatives:

- **Deferrable constraints for journal balance**: Implemented as an application-level check in `PostJournal` Action instead of a database trigger.
- **CHECK constraints for stock non-negative**: Implemented via a MySQL CHECK constraint (supported since MySQL 8.0.16) on `stock_balances.quantity >= 0`.
- **Partial indexes**: Approximated with regular composite indexes; covered queries rely on application-level filtering.
- **NUMERIC precision**: MySQL `DECIMAL(18,2)`, `DECIMAL(18,4)`, `DECIMAL(14,3)` are used — equivalent to PostgreSQL `NUMERIC`.

## Consequences
**Positive:**
- Immediately deployable on the available infrastructure.
- MySQL 26.10.0 supports CHECK constraints, JSON columns, window functions, and CTEs — covering most critical requirements.
- Laravel migrations are portable; migration to PostgreSQL later requires only minor DDL review.

**Negative:**
- No transactional DDL: a failed migration cannot be rolled back automatically.
- Deferrable constraints not available; journal balance enforced at application layer (more code, same correctness).
- Some PostgreSQL-specific optimizations (partial indexes) unavailable.
- Must document any MySQL-specific SQL if used.

**Migration path to PostgreSQL:** If hosting changes to support PostgreSQL 17, the migration path is: export data → create PostgreSQL schema from same migrations → import → add deferrable constraint trigger for journals → validate.
